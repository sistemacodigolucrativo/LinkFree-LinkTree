<?php

namespace App\Http\Controllers;

use App\Services\MemberFeatureCatalog;
use App\Services\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PublicCatalogController extends Controller
{
    private function show(Request $request, string $page): View
    {
        // Domínios destinados a perfis de membros não exibem o catálogo
        // institucional da plataforma.
        abort_if(PublicSeo::customDomain($request) !== null, 404);

        return view('public.catalog', [
            'page' => $page,
            'groups' => $page === 'resources' ? MemberFeatureCatalog::groups() : [],
            'upcoming' => $page === 'roadmap' ? MemberFeatureCatalog::upcoming() : [],
        ]);
    }

    public function resources(Request $request): View
    {
        return $this->show($request, 'resources');
    }

    public function roadmap(Request $request): View
    {
        return $this->show($request, 'roadmap');
    }
}
