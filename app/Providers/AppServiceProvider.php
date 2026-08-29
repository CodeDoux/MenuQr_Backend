<?php

namespace App\Providers;

use App\Models\Categorie;
use App\Models\Commande;
use App\Models\Employe;
use App\Models\Horaire;
use App\Models\JournalActivite;
use App\Models\Livraison;
use App\Models\Menu;
use App\Models\MoyenPaiement;
use App\Models\PersonalAccessToken;
use App\Models\Poste;
use App\Models\Produit;
use App\Models\Promotion;
use App\Models\QRCode;
use App\Models\Salle;
use App\Models\TableRestaurant;
use App\Models\ZoneLivraison;
use App\Policies\CategoriePolicy;
use App\Policies\CommandePolicy;
use App\Policies\EmployePolicy;
use App\Policies\HorairePolicy;
use App\Policies\JournalActivitePolicy;
use App\Policies\LivraisonPolicy;
use App\Policies\MenuPolicy;
use App\Policies\MoyenPaiementPolicy;
use App\Policies\PostePolicy;
use App\Policies\ProduitPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\QRCodePolicy;
use App\Policies\SallePolicy;
use App\Policies\TableRestaurantPolicy;
use App\Policies\ZoneLivraisonPolicy;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Gate::policy(Menu::class, MenuPolicy::class);
        Gate::policy(Categorie::class, CategoriePolicy::class);
        Gate::policy(Produit::class, ProduitPolicy::class);
        Gate::policy(Salle::class, SallePolicy::class);
        Gate::policy(TableRestaurant::class, TableRestaurantPolicy::class);
        Gate::policy(QRCode::class, QRCodePolicy::class);
        Gate::policy(Poste::class, PostePolicy::class);
        Gate::policy(Employe::class, EmployePolicy::class);
        Gate::policy(Commande::class, CommandePolicy::class);
        Gate::policy(ZoneLivraison::class, ZoneLivraisonPolicy::class);
        Gate::policy(Livraison::class, LivraisonPolicy::class);
        Gate::policy(Horaire::class, HorairePolicy::class);
        Gate::policy(MoyenPaiement::class, MoyenPaiementPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(JournalActivite::class, JournalActivitePolicy::class);
    }
}

>