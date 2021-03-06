<?php 
/**
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\OwnCollab_GanttChart\Controller;

use OC\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use OCP\IConfig;
use OCP\AppFramework\Http\DataResponse;
use OCP\Security\IHasher;
use OCP\Security\ICrypto;
use OCP\ISession;
use OCP\AppFramework\IAppContainer;
use OCP\IURLGenerator;
use OCA\OwnCollab_GanttChart\Service\TaskService;
use OCA\OwnCollab_GanttChart\Service\LinkService;
use OCA\OwnCollab_GanttChart\Service\GroupUserService;


class ShareController extends Controller {

	const DEFAULT_CIPHER = 'AES-256-CTR';

	/** @var ISecureRandom */
	private $secureRandom;
	/** @var IConfig */
	private $config;
	/** @var IHasher */
	private $hasher;
	/** @var ICrypto */
	private $crypto;
	/** @var ISession */
	private $session;
	/** var $IAppContainer */
	private $appContainer;
	/** @var IURLGenerator */
	protected $urlGenerator;

	private $taskService;
	private $linkService;
	private $groupUserService;
	private $userId;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param Manager $userManager
	 * @param ISecureRandom $secureRandom
	 * @param IConfig $config
	 * @param IHasher $hasher
	 * @param ICrypto $crypto
	 * @param ISession $session
	 * @param IAppContainer $appContainer
	 * @param IURLGenerator $urlGenerator
	 * @param TaskService $taskService
	 * @param LinkService $linkService
	 * @param GroupUserService $groupUserService
	 */
	public function __construct($appName,
								IRequest $request,
								ISecureRandom $secureRandom,
								IConfig $config, 
								IHasher $hasher,
								ICrypto $crypto,
								ISession $session,
								IAppContainer $appContainer,
								IURLGenerator $urlGenerator,
								TaskService $taskService,
								LinkService $linkService,
								GroupUserService $groupUserService,
								$UserId) {
		parent::__construct($appName, $request);
		$this->secureRandom = $secureRandom;
		$this->config = $config;
		$this->hasher = $hasher;
		$this->crypto = $crypto;
		$this->session = $session;
		$this->userId = $UserId;
		$this->appContainer = $appContainer;
		$this->urlGenerator = $urlGenerator;
		$this->taskService = $taskService;
		$this->linkService = $linkService;
		$this->groupUserService = $groupUserService;
	}


	/**
	 * Check wether the share is password protected or not
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 *
	 * @param string $token
	 * @return NotFoundResponse|TemplateResponse|RedirectResponse|JSON
	 */

	public function showAuth($token){
		$storedToken = $this->config->getAppValue('owncollab_ganttchart', 'sharetoken', 0);
		$today = $this->getToday();
		$expiryDate = $this->getExpiryDate();
		$expired = $this->checkExpired($today, $expiryDate);
		if ($token !== $storedToken){
			return new NotFoundResponse;
		}
		if ($token === $storedToken){
			if ($expired === true){
				return new TemplateResponse($this->appName, '410', [], 'guest');
			} else {
				$passwordSet = $this->getPasswordSet();
				if ($passwordSet !== 0){
					return new TemplateResponse($this->appName, 'authenticate', [], 'guest');
				} else {
					return new TemplateResponse($this->appName, 'public', ['token' => $token]);
				}
			}
		}
	}

	/**
	 * Check the password and return page
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @param string $token
	 * @param string $password
	 * @return NotFoundResponse|TemplateResponse|RedirectResponse|JSON
	 */

	public function getAuth($token, $password){
		$verification = $this->verifyPassword($password);
		if ($verification === true){
			return new TemplateResponse($this->appName, 'public', ['token' => $token]);
		}
		return new TemplateResponse($this->appName, 'authenticate', ['wrongpw' => true], 'guest');
	}

	/**
	 * Return the share object
     * @NoAdminRequired
	 *
	 * @return JSON
	 */

	public function index(){
		$token = $this->getToken();
		$expiryDate = $this->getExpiryDate();
		$passwordSet = $this->getPasswordSet();
		return [
			'token' => $token,
			'expiryDate' => $expiryDate,
			'passwordSet' => $passwordSet
		];
	}

	/**
	 * SetPassword
	 *
	 * @param string $password
	 */
	public function setPassword($password){
		return $this->storePassword($password);
	}

	/**
	* @param string $recipients
	*/
	public function sendEmail($recipients){
		$replacements = array(';', ' ');
		$recipients = str_replace($replacements, '', $recipients);
		$recipientsArray = explode(',', $recipients);		
		$token = $this->config->getAppValue('owncollab_ganttchart', 'sharetoken', 0);
		if ($token === 0){
			$token = $this->generateToken();
		}
		$link = $this->urlGenerator->linkToRouteAbsolute('owncollab_ganttchart.share.showAuth', ['token' => $token]);
		$displayname = \OC_User::getDisplayName();
		$shareExpiryDate = $this->getExpiryDate();
		$defaults = new \OC_Defaults();
		$from = \OCP\Util::getDefaultEmailAddress('lostpassword-noreply');
		$mailer = \OC::$server->getMailer();
		$tmpl = new \OC_Template('owncollab_ganttchart', 'sendshare');
		$tmpl->assign('link', $link);
		$tmpl->assign('displayname', $displayname);
		$tmpl->assign('expirydate', $shareExpiryDate);
		$msg = $tmpl->fetchPage();
		foreach ($recipientsArray as $recipient){
			try{
				$message = $mailer->createMessage();
				$message->setTo([$recipient => 'Recipient']);
				$message->setSubject('ownCollab // ' . $displayname . ' shared a link with you');
				$message->setHtmlBody($msg);
				$message->setFrom([$from => 'ownCollab Notifier']);
				$mailer->send($message);
			} catch (\Exception $e) {
				throw new \Exception($e->getMessage());
			}
		}
		return 'success';
	}

	/**
	* @param string $password
	*/
	private function storePassword($password){
		if ($password !== 0){
			$hashed = $this->hasher->hash($password);
			$this->config->setAppValue($this->appContainer->getAppName(), 'sharepassword', $hashed);
		} else {
			$this->config->setAppValue($this->appContainer->getAppName(), 'sharepassword', 0);
		}
	}

	/**
	 * Verify Password
	 *
	 * @param string $password
	 */
	private function verifyPassword($password){
		$sharePassword =$this->getPassword();
		$verify = $this->hasher->verify($password, $sharePassword);
		return $this->hasher->verify($password, $this->getPassword());
	}

	/**
	*/
	private function getPassword(){
		return $this->config->getAppValue($this->appContainer->getAppName(), 'sharepassword', 0);
	}

	/**
	 * Generate a new access token clients can authenticate with
	 */
	public function generateToken() {
		$appConfig = $this->config;
		$token = $this->secureRandom->generate(32);
		$appConfig->setAppValue('owncollab_ganttchart', 'sharetoken', $token);
		return $token;
	}

	/**
	 * Receive alreay generated token and generate a new one of none exists
	 */
	public function getToken(){
		$token = $this->config->getAppValue('owncollab_ganttchart', 'sharetoken', 0);
		if ($token === 0){
			$token = $this->generateToken();
		}
		return $token;
	}

	/**
	 * Receive expiration date and return 0 if none exists
     * @NoAdminRequired
	 */
	public function getExpiryDate(){
		$expiryDate = $this->config->getAppValue('owncollab_ganttchart', 'shareExpiryDate', 0);
		return $expiryDate;
	}

	/**
	 * Check if a password for the share is set
     * @NoAdminRequired
	 */
	public function getPasswordSet(){
		$passwordSet = $this->config->getAppValue('owncollab_ganttchart', 'sharepassword', 0);
		if ($passwordSet == 0){
			return 0;
		} else {
			return 1;
		}
	}

    /**
     * @NoAdminRequired
	 * @PublicPage
     */
    public function getTasks() {
	return new DataResponse($this->taskService->findAll());
    }

    /**
     * @NoAdminRequired
	 * @PublicPage
     */
    public function getLinks() {
	return new DataResponse($this->linkService->findAll());
    }

    /**
     * @NoAdminRequired
	 * @PublicPage
     */
    public function getGroupUsers() {
	return new DataResponse($this->groupUserService->findAll());
    }

	/**
	 * @NoAdminReqired
	 * @PublicPage
	 * 
	 * @return string
	 */
	public function getToday(){
		return time();
	}

	/**
	 * @NoAdminReqired
	 * @PublicPage
	 * 
	 * @return bool
	 */
	public function checkExpired($today, $expiryDate){
		if (strtotime($expiryDate) < $today){
			return true;
		}
		return false;
	}
}
