<?php
namespace RKGTheme;

/**
 * Class: RKGTheme
 *
 * Klasa za registraciju css-a, js-a te za inicijalizaciju ostalih objekata za temu
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 */
class RKGTheme
{
    /**
     * init
     *
     * Pokreće izvršavanje metoda
     *
     * @return void
     */
    public function run()
    {
        $this->initComponents();
        $this->initHooks();
    }

    /**
     * enqueueSripts
     *
     * registracija stilova i skripti
     *
     * @return void
     */
    public function enqueueSripts()
    {
        wp_register_style(
            'google_fonts',
            'https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700'
        );
        wp_enqueue_style(
            'google_fonts'
        );
        wp_register_style(
            'Font_Awesome',
            'https://use.fontawesome.com/releases/v5.12.1/css/all.css'
        );
        wp_enqueue_style('Font_Awesome');
        wp_register_style(
            'cropie',
            'https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.css'
        );
        wp_enqueue_style('cropie');
        wp_register_style(
            'rkg_css',
            get_template_directory_uri().'/style.css',
            array('google_fonts', 'Font_Awesome')
        );
        wp_enqueue_style('rkg_css');
        wp_register_script(
            'cropie',
            'https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.4/croppie.min.js',
            array('jquery'),
            null,
            true
        );
        wp_enqueue_script('cropie');
        wp_register_script(
            'rkg-script',
            get_template_directory_uri().'/static/site.js',
            array('jquery'),
            1.1,
            true
        );
        wp_enqueue_script('rkg-script');
        wp_localize_script(
            'rkg-script',
            'rkgTheme',
            array(
                'ajaxurl'        => admin_url('admin-ajax.php'),
            )
        );
    }


    /**
     * enqueueScriptsAdmin
     *
     * registracija stilova i skripti koje se koriste isključivo u admin djelu
     *
     * @return void
     */
    public function enqueueScriptsAdmin()
    {
        wp_register_style(
            'rkg_css-admin',
            get_template_directory_uri().'/style-admin.css'
        );
        wp_enqueue_style('rkg_css-admin');
    }


    /**
     * initHooks
     *
     * inicijacija svih stilova i skripti
     *
     * @return void
     */
    private function initHooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueueSripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScriptsAdmin'));
    }

    /**
     * initComponents
     *
     * pokretanje objekata potrebnih za funkcioniranje svake stranice RKG teme
     *
     * @return void
     */
    private function initComponents()
    {
        $components = array(
            'Admin\\UsersList',
            'Ajax\\MembersList',
            'Ajax\\Login',
        );
        foreach ($components as $component) {
            $namespaceComponent = 'RKGTheme\\'.$component;
            $object             = new $namespaceComponent();
            $object->init();
        }
    }
}
