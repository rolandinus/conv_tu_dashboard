<?php
namespace OCA\Tuuls_Dashboard\AppInfo;

use \OCP\AppFramework\App;

use \OCA\Tuuls_Dashboard\Hooks\UserHooks;
use OCP\AppFramework\Bootstrap\IRegistrationContext;


class Application extends App  {

    public function __construct(array $urlParams=array()){
        parent::__construct('tuuls_dashboard', $urlParams);

    }


    /**
     * @param IRegistrationContext $context
     */
    public function register(IRegistrationContext $context): void {
    }

}
