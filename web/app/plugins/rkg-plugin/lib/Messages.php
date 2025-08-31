<?php
namespace RKGeronimo;

use RKGeronimo\Interfaces\InitInterface;
use RKGeronimo\Helpers\Superglobals;
use Timber;

/**
 * Class: Messages
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 * @see InitInterface
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class Messages implements InitInterface
{
    /**
     * superglobals
     *
     * @var RKGeronimo\Helpers\Superglobals
     */
    public $superglobals;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->superglobals = new Superglobals();
    }

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        add_action('init', array($this, 'betaMessageCookie'));
    }

    /**
     * betaMessageCookie
     *
     * @return void
     */
    public function betaMessageCookie()
    {
        // $cookies = $this->superglobals->getCookies();
        // if (!array_key_exists('betaMessage')) {
            // setcookie('betaMessage', flase);
            // add_filter('timber_context', array($this, 'addBetaMessgaeToContext'));
        // }
    }

    /**
     * addBetaMessgaeToContext
     *
     * @param string $context context['this'] Being the Twig's {{ this }}.
     *
     * @return array
     *
     * This is where you add some context
     */
    public function addBetaMessgaeToContext($context)
    {
        // $context['betaMessage'] = true;

        return $context;
    }
}
