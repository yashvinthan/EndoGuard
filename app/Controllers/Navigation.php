<?php

/**
 * EndoGuard ~ Embedded & Internal security framework
 * Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.online)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.online)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.endoguard.online endoguard(tm)
 */

declare(strict_types=1);

namespace EndoGuard\Controllers;

class Navigation extends Base {
    public \EndoGuard\Views\Base $response;

    public function beforeroute(): void {
        // CSRF assignment in base page
        $this->response = new \EndoGuard\Views\Frontend();
    }

    /**
     * kick start the View, which creates the response
     * based on our previously set content data.
     * finally echo the response or overwrite this method
     * and do something else with it.
     */
    public function afterroute(): void {
        echo $this->response->render();
    }

    public function visitSignupPage(): void {
        \EndoGuard\Utils\Routes::redirectIfLogged();

        $pageController = new \EndoGuard\Controllers\Pages\Signup();
        $this->response->data = $pageController->getPageParams();
    }

    public function visitLoginPage(): void {
        \EndoGuard\Utils\Routes::redirectIfLogged();

        $pageController = new \EndoGuard\Controllers\Pages\Login();
        $this->response->data = $pageController->getPageParams();
    }

    public function visitForgotPasswordPage(): void {
        \EndoGuard\Utils\Routes::redirectIfLogged();

        if (!\EndoGuard\Utils\Variables::getForgotPasswordAllowed()) {
            $this->f3->reroute('/');
        }

        $pageController = new \EndoGuard\Controllers\Pages\ForgotPassword();
        $this->response->data = $pageController->getPageParams();
    }

    public function visitPasswordRecoveringPage(): void {
        \EndoGuard\Utils\Routes::redirectIfLogged();

        $pageController = new \EndoGuard\Controllers\Pages\PasswordRecovering();
        $this->response->data = $pageController->getPageParams();
    }

    public function visitLogoutPage(): void {
        \EndoGuard\Utils\Routes::redirectIfUnlogged();

        $pageController = new \EndoGuard\Controllers\Pages\Logout();
        $this->response->data = $pageController->getPageParams();
    }
}
