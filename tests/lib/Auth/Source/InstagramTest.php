<?php

use AspectMock\Test as test;

/**
 * Test authentication to Instagram.
 */
class Test_sspmod_authinstagram_Auth_Source_Instagram extends PHPUnit_Framework_TestCase {

    public $module_config;

    public static function setUpBeforeClass() {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
    }

    protected function setUp() {
        $this->module_config = SimpleSAML_Configuration::getConfig('module_authinstagram.php');
    }

    protected function tearDown() {
        test::clean(); // remove all registered test doubles
    }

    public function testAspectMockConfigured() {
        // Ensure mocks can be configured for our service class
        $linkDouble = test::double('sspmod_authinstagram_Auth_Source_Instagram', [
            'authenticate' => null,
        ]);
        $info = ['AuthId' => 'instagram'];
        $config = ['client_id' => 'example_id', 'client_secret' => 'example_secret'];
        $state = ['SimpleSAML_Auth_Default.id' => 'authinstagram'];
        (new sspmod_authinstagram_Auth_Source_Instagram($info, $config))->authenticate($state);
        // Verify it was invoked with the expected argument
        $linkDouble->verifyInvokedOnce('authenticate', [$state]);
    }

    public function testAuthenticate() {
        // Override redirect behavior
        test::double('SimpleSAML\Utils\HTTP', [
            'redirectTrustedURL' => function () {
                throw new sspmod_authinstagram_ArgumentCaptureException('redirect called', func_get_args());
            }
        ]);

        $info = ['AuthId' => 'instagram'];
        $config = ['client_id' => 'example_id', 'client_secret' => 'example_secret'];
        $state = ['SimpleSAML_Auth_Default.id' => 'authinstagram'];

        $instagram = new sspmod_authinstagram_Auth_Source_Instagram($info, $config);

        try {
            $instagram->authenticate($state);
            $this->assertFalse(true, "Error should have been thrown by test double");
        } catch (sspmod_authinstagram_ArgumentCaptureException $e) {
            $this->assertEquals('redirect called', $e->getMessage());
            $this->assertStringStartsWith(
                'https://api.instagram.com/oauth/authorize?client_id=example_id&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthinstagram%2Flinkback.php&response_type=code&state=',
                $e->getArguments()[0],
                "First argument should be the redirect url"
            );
        }
    }

    public function testFinalStep() {
        // Override fetch behavior
        $fetch_result = '{"access_token": "at123456", "user": {"id": "id123456", "username": "username", "profile_picture": "https://cdninstagram.com/t/123.jpg", "full_name": "My Name", "bio": "", "website": ""}}';
        test::double('SimpleSAML\Utils\HTTP', ['fetch' => $fetch_result]);

        $info = ['AuthId' => 'instagram'];
        $config = ['client_id' => 'example_id', 'client_secret' => 'example_secret'];
        $state = [
            'SimpleSAML_Auth_Default.id' => 'authinstagram',
            'authinstagram:verification_code' => 'c123456'
        ];

        $instagram = new sspmod_authinstagram_Auth_Source_Instagram($info, $config);

        $instagram->finalStep($state);

        $this->assertEquals('id123456', $state['Attributes']['instagram.id'][0]);
        $this->assertEquals('username', $state['Attributes']['instagram.username'][0]);
        $this->assertEquals('https://cdninstagram.com/t/123.jpg', $state['Attributes']['instagram.profile_picture'][0]);
        $this->assertEquals('My Name', $state['Attributes']['instagram.full_name'][0]);
        $this->assertEquals('', $state['Attributes']['instagram.bio'][0]);
        $this->assertEquals('', $state['Attributes']['instagram.website'][0]);
    }


    /**
     * @expectedException SimpleSAML_Error_AuthSource
     * @expectedExceptionMessage No access_token returned - cannot proceed
     */
    public function testFinalStepNoAccessToken() {
        // Override fetch behavior
        $fetch_result = '{}';
        test::double('SimpleSAML\Utils\HTTP', ['fetch' => $fetch_result]);

        $info = ['AuthId' => 'instagram'];
        $config = ['client_id' => 'example_id', 'client_secret' => 'example_secret'];
        $state = [
            'SimpleSAML_Auth_Default.id' => 'authinstagram',
            'authinstagram:verification_code' => 'c123456'
        ];

        $instagram = new sspmod_authinstagram_Auth_Source_Instagram($info, $config);

        $instagram->finalStep($state);
    }

}