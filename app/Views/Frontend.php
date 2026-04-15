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

namespace EndoGuard\Views;

class Frontend extends Base {
    public function render(): string|false|null {
        if ($this->data) {
            $this->f3->mset($this->data);
        }

        \EndoGuard\Utils\Routes::callExtra('FRONTEND_VIEW');

        // Use anti-CSRF token in templates.
        $this->f3->set('CSRF', $this->f3->get('SESSION.csrf'));

        $tpl = $this->f3->get('TPL') ?? null;
        if ($tpl) {
            $tpl::registerExtends();
        }

        return \Template::instance()->render('templates/layout.html');
    }
}
