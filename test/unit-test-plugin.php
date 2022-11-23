<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-outline-vpn/disciple-tools-outline-vpn.php' );

        $this->assertContains(
            'disciple-tools-outline-vpn/disciple-tools-outline-vpn.php',
            get_option( 'active_plugins' )
        );
    }
}
