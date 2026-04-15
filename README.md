# endoguard

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/ec30c28f67de476f8b98d2798079bdf0)](https://app.codacy.com/gh/endoguardTechnologies/endoguard/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Docker Pulls](https://img.shields.io/docker/pulls/endoguard/endoguard?style=flat)](https://hub.docker.com/r/endoguard/endoguard/)

<p align="center">
    <a href="https://www.endoguard.io/" target="_blank">
        <img src="https://www.endoguard.io/firstscreen.jpg" alt="endoguard screenshot" />
    </a>
</p>

[endoguard](https://www.endoguard.io) is an open-source security framework.

endoguard helps understand, monitor, and protect your product from threats, fraud, and abuse. While classic cybersecurity focuses on infrastructure and network perimeter, most breaches occur through compromised accounts and application logic abuse that bypasses firewalls, SIEM, WAFs, and other defenses. endoguard detects threats where they actually happen: inside your product.

endoguard is a hand-written, few-dependency, "low-tech" PHP/PostgreSQL application. After a straightforward five-minute installation, you can ingest events through API calls and immediately access a real-time threat dashboard.

## Core components
* **SDKs & API** Integrate endoguard into any product with SDKs.
  Send events with full context in a few lines of code.
* **Built-in dashboard** Monitor and understand your product's
  security events from a single interface. Ready for use in minutes.
* **Single user view** Analyze behaviour patterns, risk scores,
  connected identities, and activity timelines for a specific user.
* **Rule engine** Calculate risk scores automatically with preset
  rules or create your own customized for your product.
* **Review queue** Automatically suspend accounts with risky events
  or flag them for manual review through threshold settings.
* **Field audit trail** Track modifications to important fields,
  including what changed and when to streamline audit and compliance.

## Preset rules

`Account takeover` `Credential stuffing` `Content spam` `Account registration` `Fraud prevention` `Insider threat`
`Bot detection` `Dormant account` `Multi-accounting` `Promo abuse` `API protection` `High-risk regions`

## Built for

* **Self-hosted, internal and legacy apps**: Embed security layer
  to extend your security through audit trails, protect user accounts
  from takeover, detect cyber threats and monitor insider threats.
* **SaaS and digital platforms**: Prevent cross-tenant data leakage,
  online fraud, privilege escalation, data exfiltration and business
  logic abuse.
* **E-commerce and online marketplaces**: Detect payment fraud, account 
  abuse, fake reviews, promotional code exploitation, inventory manipulation,
  and protect against credential stuffing and carding attacks.
* **Mission critical applications**: Sensitive application protection,
  even in air-gapped deployments.
* **Industrial control systems (ICS) and command & control (C2)**: Protect,
  operational technology, command systems, and critical infrastructure
  platforms from unauthorized access and malicious commands.
* **Non-human identities (NHIs)**: Monitor service accounts, API keys,
  bot behaviors, and detect compromised machine identities.
* **API-first applications**: Protect against abuse, rate limiting
  bypasses, scraping, and unauthorized access.

## Live demo

Check out the live demo at [play.endoguard.io](https://play.endoguard.io) (*admin/endoguard*).

## Requirements

* **PHP**: Version 8.0 to 8.3
* **PostgreSQL**: Version 12 or greater
* **PHP extensions**: `PDO_PGSQL`, `cURL`
* **HTTP web server**: `Apache` with `mod_rewrite` and `mod_headers` enabled
* **Operating system**: A Unix-like system is recommended
* **Minimum hardware requirements**:
  * **PostgreSQL**: 512 MB RAM (4 GB recommended)
  * **Application**: 128 MB RAM (1 GB recommended)
  * **Storage**: Approximately 3 GB PostgreSQL storage per 1 million events

## Docker-based installation

To run endoguard within a Docker container you may use command below:

```bash
curl -sL endoguard.io/t.yml | docker compose -f - up -d
```
Continue with step 4 of [Quickstart](#quickstart-install).

## Quickstart install
1. [Download](https://www.endoguard.io/download/) the latest version of endoguard (ZIP file).
2. Extract the endoguard-master.zip file to the location where you want it installed on your web server.
3. Navigate to `http://localhost:8585/install/index.php` in a browser to launch the installation process.
4. After the successful installation, delete the `install/` directory and its contents.
5. Navigate to `http://localhost:8585/signup/` in a browser to create an administrator account.
6. For cron job setup, insert the following schedule (every 10 minutes) expression with the `crontab -e` command or by editing the `/var/spool/cron/your-web-server` file:

```
*/10 * * * * /usr/bin/php /absolute/path/to/endoguard/index.php /cron
```

## Using Heroku (optional)

Click [here](https://heroku.com/deploy?template=https://github.com/endoguardtechnologies/endoguard) to launch heroku deployment.

## Via Composer and Packagist (optional)

endoguard is published at Packagist and could be installed with Composer:

```
composer create-project endoguard/endoguard
```

or could be pulled into an existing project:

```
composer require endoguard/endoguard
```

## SDKs

* [PHP](https://github.com/endoguardtechnologies/endoguard-php-tracker)
* [Python](https://github.com/endoguardtechnologies/endoguard-python-tracker)
* [NodeJS](https://github.com/endoguardtechnologies/endoguard-nodejs-tracker)
* [WordPress](https://github.com/endoguardtechnologies/endoguard-wordpress-tracker)
  
## Documentation

See the [User guide](https://docs.endoguard.io/) for details on how to use EndoGuard, [Developers documentation](https://github.com/endoguardtechnologies/DEVELOPMENT.md) to customize your integration, [Admin documentation](https://github.com/endoguardtechnologies/ADMIN.md) for installation, maintenance and updates.

## About

endoguard is an [open-source security framework](https://www.endoguard.io) that embeds protection against threats, fraud, and abuse right into your product.

The project started as a proprietary system in 2021 and was open-sourced (AGPL) in December 2024.

Behind endoguard is a blend of extraordinary engineers and professionals, with over a decade of experience in cyberdefence. We solve real people's challenges through love in *ascétique* code and open technologies. endoguard is not VC-motivated. Our inspiration comes from the daily threats posed by organized cybercriminals, driving us to reimagine the place of security in modern applications.

## Why the name endoguard?

The name **endoguard** is derived from the Greek prefix *endo-* (meaning internal, within) and the English word *guard*. This reflects the project's core philosophy: security should not just be a perimeter wall, but an embedded layer that protects an application from the inside out.

While classic security tools monitor the network, endoguard monitors the "gut" of the application — user behavior, business logic transitions, and internal events. It stands as a silent sentinel within your code, providing a baseline of trust and detection where threats actually manifest.

The endoguard wordmark stands behind the horizon line, as a metaphor of the endless evolutionary cycle of the threat landscape and our commitment to rise over it.

## Links

* [Website](https://www.endoguard.io)
* [Live demo](https://play.endoguard.io)
* [Admin documentation](https://github.com/endoguardtechnologies/ADMIN.md)
* [Developers documentation](https://github.com/endoguardtechnologies/DEVELOPMENT.md)
* [Resource center](https://www.endoguard.io/bat/)
* [Docker Hub](https://hub.docker.com/r/endoguard/endoguard)
* [User guide](https://docs.endoguard.io)
* [Packagist](https://packagist.org/packages/endoguard/endoguard)
* [Mattermost community](https://chat.endoguard.io)
  
## Reporting a security issue

If you've found a security-related issue with endoguard, please email security@endoguard.io. Submitting the issue on GitHub exposes the vulnerability to the public, making it easy to exploit. We will publicly disclose the security issue after it has been resolved.

After receiving a report, endoguard will take the following steps:

* Confirm that the report has been received and is being addressed.
* Attempt to reproduce the problem and confirm the vulnerability.
* Release new versions of all the affected packages.
* Announce the problem prominently in the release notes.
* If requested, give credit to the reporter.

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License (AGPL) as published by the Free Software Foundation version 3.

The name "endoguard" is a registered trademark of EndoGuard Security sàrl, and EndoGuard Security sàrl hereby declines to grant a trademark license to "endoguard" pursuant to the GNU Affero General Public License version 3 Section 7(e), without a separate agreement with EndoGuard Security sàrl.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along with this program. If not, see [GNU Affero General Public License v3](https://www.gnu.org/licenses/agpl-3.0.txt).

## Authors

endoguard Copyright (C) 2026 EndoGuard Security sàrl, Vaud, Switzerland. (License AGPLv3)

't'
