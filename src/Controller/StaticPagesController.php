<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Contracts\Translation\TranslatorInterface;

class StaticPagesController extends Controller
{

    private $page_config = array();

    private $pages = array();

    public function __construct()
    {
        // Raw pages data
        // $company = array(
        //     'name'        => 'Ingeneria',
        //     'address'     => '4 rue Gerin Ricard 13003 Marseille - France (métropolitaine)',
        //     'capital'     => '1 500€',
        //     'social_form' => 'SAS (Société par actions simplifiée)',
        //     'url'         => 'Ateliers-ingeneria.fr',
        //     'vta_number'  => '',
        //     'siren' => array(
        //         'number' => '752 183 988',
        //         'address' => 'Marseille B'
        //     )
        // );

        $owner = array(
            'firstname' => 'Aurélien',
            'lastname'  => 'Litti',
            'phone'     => '+33 6 95 06 40 91',
            'email'     => 'litti.aurelien@gmail.com'
        );

        $developer = array(
            'firstname' => 'Aurélien',
            'lastname'  => 'Litti',
            'phone'     => '+33 6 95 06 40 91',
            'email'     => 'litti.aurelien@gmail.com'
        );

        $web_host = array(
            'name'        => 'OVH',
            'address'     => '2 rue Kellerman – BP 80157 – 59100 Roubaix – France',
            'social_form' => 'SAS (Société par actions simplifiée)',
            'phone'       => '+33 820 698 765'
        );

        // Pages list
    }

    // ML: @Route("/{_locale}/{slug}.html", name="static_pages")
    /**
     * @Route("/{slug}.html", name="static_pages")
     */
    public function index($slug)
    {
        $translator = $this->get('translator');

        // Get page config according to given slug
        switch ($slug) {
            // DATA: About page
            case 'a-propos':
            case 'about':
                $this->page_config = array(
                    'template' => 'static-pages/about.html.twig',
                    'data' => array(
                        'meta' => [
                            'title'   => $translator->trans('page.about.title'),
                            'robots'  => 'noindex, nofollow'
                        ]
                    )
                );
              break;
            // DATA: Release notes page
            case 'notes-de-version':
            case 'release-notes':
                $this->page_config = array(
                    'template' => 'static-pages/release_notes.html.twig',
                    'data' => array(
                        'meta' => [
                            'title'   => $translator->trans('page.release_notes.title'),
                            'robots'  => 'noindex, nofollow'
                        ]
                    )
                );
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

    private function is_config_valid() {
        return (!empty($this->page_config)
            && isset($this->page_config['data'])
                && isset($this->page_config['template'])
        );
    }
}
