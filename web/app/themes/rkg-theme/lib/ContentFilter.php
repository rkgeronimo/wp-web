<?php
namespace RKGTheme;

/**
 * Class: ContentFilter
 *
 * Čišćenje sadržaja tečajeva i izleta iz klasičnog editora
 *
 */
class ContentFilter
{
    /**
     * init
     *
     * Inicijalizacija potrebnih akcija i filtara
     *
     * @return void
     */
    public function init()
    {
        // after wpautop (10), which turns blank lines into <p>&nbsp;</p>
        add_filter('the_content', array($this, 'removeEmptyParagraphs'), 20);
    }

    /**
     * removeEmptyParagraphs
     *
     * Uklanja odlomke koji sadrže samo razmake, &nbsp; ili <br>. Tečajevi i
     * izleti koriste klasični editor, koji za svaki prazni red sprema
     * &nbsp;, pa bi se na stranici pojavile velike praznine.
     *
     * @param string $content
     *
     * @return string
     */
    public function removeEmptyParagraphs($content)
    {
        if (!in_array(get_post_type(), array('course', 'excursion'), true)) {
            return $content;
        }

        return preg_replace(
            '#<p[^>]*>(?:\s|&nbsp;|&\#160;|\xC2\xA0|<br\s*/?>)*</p>#iu',
            '',
            $content
        );
    }
}
