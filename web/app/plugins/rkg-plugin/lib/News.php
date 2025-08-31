<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;
use Timber;

/**
 * Class: News
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class News implements InitInterface
{
    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_shortcode('rkg-new', array($this, 'newsBlock'));
        add_action('wp_ajax_news_block_update', array($this, 'newsBlockUpdate'));
        add_action('wp_ajax_nopriv_news_block_update', array($this, 'newsBlockUpdate'));
    }

    /**
     * rkgNewsBlock
     *
     * @return string
     */
    public function newsBlock()
    {
        $context          = Timber::get_context();
        $context['posts'] = Timber::get_posts(
            array(
                'numberposts' => 6,
                'post_status' => 'publish',
                'post_type' => 'rkg-post'
            )
        );

        foreach ($context['posts'] as $key => $value) {
            $context['posts'][$key]->categories = get_the_category(
                $context['posts'][$key]->ID
            );

            $coverImageId = $context['posts'][$key]->cover_image;

            $context['posts'][$key]->cover_image = new Timber\Image($coverImageId);
        }

        $context['categories'] = get_categories();
        $templates             = array( 'newsBlock.twig' );

        return Timber::compile($templates, $context);
    }

    public function newsBlockUpdate()
    {
        $categorie        = $_POST['id'];
        $context          = Timber::get_context();
        $args = array(
            'numberposts' => 6,
            'post_status' => 'publish',
            'post_type' => 'rkg-post'
        );
        if ($categorie != 'all') {
            $args['category'] = $categorie;
        }
        $context['posts'] = Timber::get_posts($args);

        foreach ($context['posts'] as $key => $value) {
            $context['posts'][$key]->categories = get_the_category(
                $context['posts'][$key]->ID
            );

            $coverImageId = $context['posts'][$key]->cover_image;

            $context['posts'][$key]->cover_image = new Timber\Image($coverImageId);
        }

        $context['categories'] = get_categories();
        $context['categorie_id'] = $categorie;
        $templatesCategorie    = array('newsBlockCategory.twig');
        $templatesContent      = array('newsBlockContent.twig');
        $json['category']      = Timber::compile($templatesCategorie, $context);
        $json['content']       = Timber::compile($templatesContent, $context);

        echo  json_encode($json);
        wp_die();
    }
}
