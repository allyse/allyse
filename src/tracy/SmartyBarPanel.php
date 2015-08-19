<?php

class SmartyBarPanel implements Tracy\IBarPanel
{

    private $smarty;

    public function __construct ($smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * Renders HTML code for custom tab.
     *
     * @return string
     */
    public function getTab ()
    {
        ob_start();
        $smarty = $this->smarty;
        require BOOTSTRAP_DIR . '/tracy/assets/Bar/smarty.tab.phtml';

        return ob_get_clean();
    }

    /**
     * Renders HTML code for smarty panel.
     *
     * @return string
     */
    public function getPanel ()
    {
        ob_start();
        if (is_file(BOOTSTRAP_DIR . '/tracy/assets/Bar/smarty.panel.phtml')) {
            $smarty = $this->smarty;
            require BOOTSTRAP_DIR . '/tracy/assets/Bar/smarty.panel.phtml';
        }

        return ob_get_clean();
    }

}
