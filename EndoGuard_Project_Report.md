# EndoGuard: Open-Source Embedded Security Framework
**Project Report**

---

## 1. Executive Summary
**EndoGuard** is a sophisticated, open-source security framework designed to protect modern web applications, SaaS platforms, and digital products from within. Unlike traditional perimeter-based security solutions that focus on network boundaries, EndoGuard embeds protection directly into the application logic. It specializes in detecting and preventing threats like account takeovers, fraud, and business logic abuse by monitoring real-time behavioral patterns.

---

## 2. Problem Statement: The Security Gap
Traditional cybersecurity architectures rely heavily on "Castle and Moat" defenses:
*   **Firewalls & WAFs:** Effective against known network signatures but blind to legitimate-looking traffic that abuses application logic.
*   **Infrastructure Security:** Protects the server, but not the user account or the business process.

**The Reality of Modern Breaches:**
Most modern security incidents occur through **Compromised Identities** and **Application Logic Abuse**. This includes:
*   **Account Takeover (ATO):** Using stolen credentials to bypass external defenses.
*   **Credential Stuffing:** Large-scale automated login attempts.
*   **Promo & Business Abuse:** Exploiting logic flaws in checkout or registration processes.
*   **Insider Threats:** Malicious actions taken by authorized users.

EndoGuard was built to solve the "last mile" of security by being present where the activity actually happens: **Inside the product.**

---

## 3. The Solution: Embedded Protection
EndoGuard provides a "Low-Tech" but highly effective PHP/PostgreSQL stack that acts as a universal security layer.

### Key Principles:
1.  **Context-Aware Ingestion:** Ingests events with the full application context (User ID, IP, Device ID, Action Type).
2.  **Embedded Security:** Lives alongside your code, integrating via SDKs to monitor internal state changes.
3.  **Real-Time Assessment:** Processes every event through a flexible rule engine to calculate risk scores instantly.
4.  **Actionable Intelligence:** Provides a real-time threat dashboard, automated review queues, and detailed audit trails.

---

## 4. Technical Stack
EndoGuard is built for stability, portability, and minimal dependency overhead.

*   **Language:** PHP 8.1+ (Leveraging modern features for performance).
*   **Database:** PostgreSQL 12+ (Utilizing advanced indexing and JSONB for event storage).
*   **Framework:** Fat-Free Framework (F3) Core (A fast, powerful, and lightweight PHP framework).
*   **Rule Engine:** Ruler (A robust engine for evaluating complex business rules).
*   **Device Analysis:** Matomo Device Detector (Expert-level parsing of user-agent data).
*   **Deployment:** Supports Docker, Heroku, and standard PHP/Apache/Nginx environments.

---

## 5. System Workflow
The lifecycle of security event in EndoGuard follows a structured "Sense-Process-Shield" path:

1.  **Event Trigger (SDK):** When a user performs a sensitive action (e.g., *Login*, *Withdrawal*, *Change Password*), the application calls the EndoGuard SDK.
2.  **API Ingestion:** The event is sent to the EndoGuard API Endpoint.
3.  **Context Enrichment:**
    *   **IP Intelligence:** Extracts geolocation, ISP, and proxy/VPN status.
    *   **Device Fingerprinting:** Parsers identify the browser, OS, and hardware profile.
    *   **Historical Context:** Compares the action against the user’s past behavior patterns.
4.  **Rule Execution:** The event is evaluated against **Preset Rules** (e.g., *Dormant Account Awakening*) or **Custom Rules** defined by the administrator.
5.  **Risk Scoring:** A risk score (0-100) is calculated. 
    *   *Low Risk:* Event is logged normally.
    *   *Medium Risk:* Event is flagged for manual review.
    *   *High Risk:* Triggers immediate actions such as account suspension or MFA requirement.
6.  **Reporting & Monitoring:** Admins monitor the "Pulse" dashboard for real-time visibility and manage the "Review Queue" to resolve alerts.

---

## 6. Core Features
*   **SDK-First Integration:** Libraries for PHP, Python, Node.js, and WordPress enable integration in minutes.
*   **Custom Rule Engine:** Create complex logic like "Flag if a user logs in from a new country and changes their email within 10 minutes."
*   **Field Audit Trail:** Track every modification to sensitive database fields with "Before" and "After" snapshots.
*   **Single User Timeline:** A 360-degree view of individual user behavior, connected identities, and risk history.
*   **Review Queue:** A structured workspace for security analysts to approve or block suspicious activities.

---

## 7. Conclusion
**EndoGuard** redefines application security by moving the focus from the perimeter to the heart of the business logic. By providing a transparent, developer-friendly, and open-source framework, EndoGuard empowers organizations to protect their users and their data from the evolving landscape of organized cybercrime and sophisticated fraud.

---
*Report generated for EndoGuard Project.*
