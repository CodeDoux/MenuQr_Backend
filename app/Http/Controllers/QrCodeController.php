<?php

namespace App\Http\Controllers;

use App\Enums\TypeQRCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\QrCodeGeneralRequest;
use App\Http\Resources\QrCodeResource;
use App\Models\QRCode;
use App\Models\Salle;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

/**
 * ⚠️ Génération server-side (contrairement au frontend qui la faisait
 * côté client) : permet de stocker une vraie image persistée et de
 * suivre nombre_scan de façon fiable. FRONTEND_URL (.env) nécessaire
 * car le backend ne connaît pas l'origine du frontend automatiquement.
 */
class QrCodeController extends Controller
{
    public function __construct(private readonly TenantContext $tenant) {}

    /** Génère (ou régénère) le QR d'une table précise. */
    public function genererPourTable(string $salle, string $tableId)
    {
        $salleModel = Salle::findOrFail($salle);
        $table = $salleModel->tables()->findOrFail($tableId);
        Gate::authorize('create', QRCode::class);

        // Désactive l'éventuel QR de table déjà actif pour cette table
        QRCode::where('table_id', $table->id)->where('est_actif', true)->update(['est_actif' => false]);

        $qrCode = $this->creerQrCode(TypeQRCode::TABLE, $table->id);

        return new QrCodeResource($qrCode);
    }

    /** Génère (ou régénère) un QR général (emporter/livraison, non lié à une table). */
    public function genererGeneral(QrCodeGeneralRequest $request)
    {
        Gate::authorize('create', QRCode::class);

        $type = TypeQRCode::from($request->validated('type'));

        QRCode::where('restaurant_id', $this->tenant->restaurantId)
            ->where('type', $type)
            ->whereNull('table_id')
            ->where('est_actif', true)
            ->update(['est_actif' => false]);

        $qrCode = $this->creerQrCode($type, null);

        return new QrCodeResource($qrCode);
    }

    public function index()
    {
        Gate::authorize('viewAny', QRCode::class);

        $qrCodes = QRCode::where('est_actif', true)->get();

        return QrCodeResource::collection($qrCodes);
    }

    public function desactiver(string $id)
    {
        $qrCode = QRCode::findOrFail($id);
        Gate::authorize('delete', $qrCode);

        $qrCode->update(['est_actif' => false]);

        return response()->json(null, 204);
    }

    private function creerQrCode(TypeQRCode $type, ?string $tableId): QRCode
    {
        $code = Str::upper(Str::random(10));
        $urlBase = config('app.frontend_url');
        $url = "{$urlBase}/m/{$this->tenant->restaurantId}?code={$code}";
        if ($tableId) {
            $url .= "&table={$tableId}";
        } else {
            $url .= '&mode='.$type->value;
        }

        $pngBinaire = QrCodeGenerator::format('svg')->size(320)->margin(1)->generate($url);
        $chemin = "qrcodes/{$this->tenant->restaurantId}/".Str::uuid().'.svg';
        Storage::disk('public')->put($chemin, $pngBinaire);
        $imageUrl = Storage::disk('public')->url($chemin);

        return QRCode::create([
            'table_id' => $tableId,
            'code' => $code,
            'url' => $url,
            'image' => $imageUrl,
            'type' => $type,
            'date_expiration' => null,
            'nombre_scan' => 0,
            'est_actif' => true,
        ]);
    }
}