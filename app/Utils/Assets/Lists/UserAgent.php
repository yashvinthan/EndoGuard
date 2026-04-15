<?php

/**
 * EndoGuard ~ Embedded & Internal security framework
 * Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.io)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.io)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.endoguard.io endoguard(tm)
 */

declare(strict_types=1);

namespace EndoGuard\Utils\Assets\Lists;

class UserAgent extends Base {
    protected static string $extensionFile = 'user-agent.php';

    protected static array $list = [
        '--',
        '/*',
        '*/',
        'pg_',
        '\');',     // should be %'%)%;% ?
        'alter ',
        'select',
        'waitfor',
        'delay',
        'delete',
        'drop',
        'dbcc',
        'schema',
        'exists',
        'cmdshell',
        '%2A',      // *
        '%27',      // '
        '%22',      // "
        '%2D',      // -
        '%2F',      // /
        '%5C',      // \
        '%3B',      // ;
        '%23',      // #
        '%2B',      // +
        '%3D',      // =
        '%28',      // (
        '%29',      // )
        '/bin',
        '%2Fbin',
        '.sh',
        '|sh',
        '.exe',
    ];
}
