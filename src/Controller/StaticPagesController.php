<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class StaticPagesController extends AbstractController
{

    private $page_config = [];

    // ML: @Route("/{_locale}/{slug}.html", name="static_pages")
    /**
     * @Route("/{slug}.html", name="static_pages")
     */
    public function index(string $slug, TranslatorInterface $translator): Response
    {
        // Get page config according to given slug
        switch ($slug) {
            // DATA: About page
            case 'a-propos':
            case 'about':
                $this->page_config = [
                    'template' => 'static-pages/about.html.twig',
                    'data' => [
                        'meta' => [
                            'title'   => $translator->trans('page.about.title'),
                            'robots'  => 'noindex, nofollow'
                        ]
                    ]
                ];
              break;
            // DATA: Release notes page
            case 'notes-de-version':
            case 'release-notes':
                $this->page_config = [
                    'template' => 'static-pages/release_notes.html.twig',
                    'data' => [
                        'meta' => [
                            'title'   => $translator->trans('page.release_notes.title'),
                            'robots'  => 'noindex, nofollow'
                        ]
                    ]
                ];
              break;
            // NOTE Add default data ? 404 ?
            default:
              break;
        }

        // If config page has been configurated, render it or redirect to dashboard
        //  (if user isn't connected = redirect to homepage)
        //    TODO add check if template exist
        if($this->is_config_valid()) {
            $data     = $this->page_config['data'];
            $template = $this->page_config['template'];

            // Force CSS class for static pages (.app-core--static-page)
            if (!isset($data['core_class'])) $data['core_class'] = 'app-core--static-page';
            else $data['core_class'] .= ' app-core--static-page';

            // Render template
            return $this->render($template, $data);
        } else {
            return $this->redirectToRoute('dashboard');
        }
    }

    private function is_config_valid(): bool
    {
        return !empty($this->page_config)
            && isset($this->page_config['data'])
            && isset($this->page_config['template'])
        ;
    }
}
