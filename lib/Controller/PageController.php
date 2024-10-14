<?php
namespace OCA\Tu_Dashboard\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\Files\Folder;


class PageController extends Controller {

	public function __construct($AppName, IRequest $request){
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
	public function index() {

		try {
			$favElements = $this->getFavoriteFilePaths(\OC::$server->getUserSession()->getUser()->getUID());
		} catch (\RuntimeException $e) {
			$favElements['items'] = [];
		}

//		return $favElements;
$favoritesSublistArray = array();
$currentCount= 0;
		// return $favElements;
        if($favElements && isset($favElements['folders'])) {
            foreach ($favElements['folders'] as $dir) {

                $link = \OC::$server->getURLGenerator()->linkToRoute('files.view.index', ['dir' => $dir['path'], 'view' => 'files']);
                $sortingValue = ++$currentCount;
                $element = [
                    'id' => str_replace('/', '-', $dir),
                    'view' => 'files',
                    'href' => $link,
                    'dir' => $dir['path'],
                    //	'order'              => $navBarPositionPosition,
                    'folderPosition' => $sortingValue,
                    'name' => $dir['name'],
                    'icon' => 'files',
                    'quickaccesselement' => 'true'
                ];
                array_push($favoritesSublistArray, $element);
            }
        }

		$user = \OC::$server->getUserSession()->getUser()->getDisplayName();
		$parameters = array('user' => $user,
												'favs' => $favElements,
												'favoritesSublistArray' => $favoritesSublistArray
											);
    return new TemplateResponse('tu_dashboard', 'index', $parameters);  // templates/index.php
	}



	private function getFavoriteFilePaths($user) {
		$tagManager = \OC::$server->getTagManager();
		$tags = $tagManager->load('files', [], false, $user);
		$favorites = $tags->getFavorites();

		if (empty($favorites)) {
			throw new \RuntimeException('No favorites', 1);
		} else if (isset($favorites[50])) {
			throw new \RuntimeException('Too many favorites', 2);
		}

		// Can not DI because the user is not known on instantiation
		$rootFolder = \OC::$server->getUserFolder($user);
		$folders = $items = [];
		foreach ($favorites as $favorite) {
			$nodes = $rootFolder->getById($favorite);
			if (!empty($nodes)) {
				/** @var \OCP\Files\Node $node */
				$node = array_shift($nodes);
				$path = substr($node->getPath(), strlen($user . '/files/'));

				//$items[] = $path;
				if ($node instanceof Folder) {
					$item = array(
						'id'	 => $node->getId(),
						'path' => $path,
						'mime' => $node->getMimePart(),
						'name' => $node->getName(),
						'etag' => $node->getEtag(),
						'size' => $node->getSize()
					);
					$folders[] = $item; //$path;
				}
				else {
					$item = array(
						'id'	 => $node->getId(),
						'path' => $path,
						'mime' => $node->getMimePart(),
						'name' => $node->getName(),
						'etag' => $node->getEtag(),
						'size' => $node->getSize()
					);
					$items[] = $item; //$path;
				}
			}
		}

		if (empty($items)) {
			throw new \RuntimeException('No favorites', 1);
		}

		return [
			'items' => $items,
			'folders' => $folders,
		];
	}
}
