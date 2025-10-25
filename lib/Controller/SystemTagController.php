<?php
/**
 * Created by PhpStorm.
 * User: roland
 * Date: 02.07.19
 * Time: 15:59
 */

namespace OCA\Tuuls_Dashboard\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class SystemTagController extends Controller
{
    private $userId;

    public function __construct($AppName, IRequest $request)
    {
        parent::__construct($AppName, $request);
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index()
    {
        $systemTagManager = \OC::$server->getSystemTagManager();
        $systemTags = $systemTagManager->getAllTags();
        $systemTagNames = [];
        foreach ($systemTags as $systemTag) {
            $systemTagNames [] = $systemTag->getName();
        }
        return new DataResponse($systemTagNames);

    }
}
