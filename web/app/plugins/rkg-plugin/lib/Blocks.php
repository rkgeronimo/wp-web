<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;

/**
 * Class: Blocks
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see Init
 *
 * Upravljanje sa blokovima koji se koriste kod pisanja tekstova
 */
class Blocks implements InitInterface
{
    /**
     * init
     *
     * @return void
     *
     * Inicijalizacija
     */
    public function init()
    {
        add_filter('allowed_block_types_all', array($this, 'allowedBlockTypes'));
    }

    /**
     * allowedBlockTypes
     *
     * @param mixed $allowedBlocks
     *
     * @return array
     *
     * Definiranje dozvoljenih blokova kod pisanja teksta
     */
    public function allowedBlockTypes($allowedBlocks)
    {
        return array(
            'core/paragraph',
            'core/image',
            'core/heading',
            'core/list',
            'core/audio',
            'core/html',
            'core/cover',
            'core/file',
            'core/video',
            'core/button',
            'core/separator',
            'core/spacer',
            'core/embed',
            'core-embed/youtube',
            'core-embed/instagram',
            'cgb/block-rkggallery',
        );
    }
}
