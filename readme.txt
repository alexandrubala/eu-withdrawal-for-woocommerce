=== EU Withdrawal for WooCommerce ===
Contributors: your-wp-org-username
Tags: woocommerce, eu directive, withdrawal, refund, return, compliance
Requires at least: 6.4
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete Art. 11a withdrawal compliance for WooCommerce: two-step flow, HMAC audit log, email proof, guest checkout & HPOS.

== Description ==

**EU Withdrawal for WooCommerce** is the complete compliance solution for **Article 11a of Directive (EU) 2023/2673**, which requires traders selling to EU consumers to provide a clear, accessible electronic means to exercise the right of withdrawal from distance and off-premises contracts.

If you operate a WooCommerce store that sells to consumers in the European Union, this plugin implements the legally required withdrawal workflow—from the customer-facing request form through immutable record-keeping and proof of receipt—so you can meet your obligations under the updated Consumer Rights Directive without custom development.

= Key features =

* **Strict two-step withdrawal flow** — Customers first identify their order and review the legal withdrawal statement, then explicitly confirm their request. This mirrors the deliberate, informed consent required under EU consumer law and reduces invalid or accidental submissions.
* **Immutable HMAC audit log** — Every withdrawal request is recorded with SHA-256 payload hashing and HMAC-SHA256 security signatures. The audit trail is tamper-evident and suitable for demonstrating compliance to supervisory authorities or in dispute resolution.
* **100% HPOS compatible** — Fully declared compatible with WooCommerce High-Performance Order Storage (custom order tables). Order meta boxes, admin links, and validation work seamlessly whether HPOS is enabled or not.
* **Full guest checkout support** — Customers do not need a WordPress account. The flow validates orders by order number and billing email, making it accessible to all purchasers regardless of how they checked out.
* **Email confirmation (durable medium)** — Upon successful submission, the customer receives an HTML confirmation email containing the request UUID, submission timestamp, and withdrawal details—satisfying the requirement to provide confirmation on a durable medium.
* **Native Gutenberg block** — Insert the withdrawal button and form anywhere using the **Withdrawal Button** block (`eu-withdrawal/withdrawal-button`) in the block editor, with optional wide/full alignment support.
* **Shortcode fallback** — Prefer classic editors or page builders? Use `[eu_withdrawal_button]` with an optional `label` attribute to render the same compliant flow.
* **Admin dashboard** — Manage all withdrawal requests from a dedicated admin screen: filter by status, search by order or customer, update request status, and inspect the full event timeline.
* **Order meta box** — View linked withdrawal requests directly on the WooCommerce order edit screen (classic and HPOS).
* **Export for authorities** — Export filtered withdrawal records as CSV or open a print-friendly view for PDF archiving from the admin dashboard.
* **Rate limiting & security** — Built-in IP rate limiting, nonce verification, and cache-aware nonce refresh protect the public endpoints from abuse.

= Legal context =

Directive (EU) 2023/2673 amends Directive 2011/83/EU on consumer rights. **Article 11a** introduces a mandatory electronic withdrawal mechanism for distance and off-premises contracts concluded on or after 19 June 2026 (with transitional provisions). This plugin is designed to help WooCommerce merchants implement that mechanism in a structured, auditable way.

*This plugin provides technical tooling to support compliance. It does not constitute legal advice. Merchants remain responsible for ensuring their overall sales practices, terms, and timelines meet applicable national transposition of EU law.*

== Installation ==

= Automatic installation =

1. Log in to your WordPress admin panel.
2. Navigate to **Plugins → Add New**.
3. Search for **EU Withdrawal for WooCommerce**.
4. Click **Install Now**, then **Activate**.

= Manual installation =

1. Upload the `eu-withdrawal-for-woocommerce` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Ensure **WooCommerce** is installed and active.

= Display the withdrawal form on your site =

After activation, add the withdrawal entry point to any page:

**Option A — Gutenberg block**

1. Edit the page where customers should start a withdrawal (e.g. “Withdrawal” or “Returns”).
2. Click **+** to add a block and search for **Withdrawal Button** (WooCommerce category).
3. Optionally customize the button label in the block sidebar.
4. Publish or update the page.

**Option B — Shortcode**

Add the following shortcode to any page, post, or widget area that supports shortcodes:

`[eu_withdrawal_button]`

Optional attribute:

`[eu_withdrawal_button label="Request withdrawal"]`

= After setup =

* Visit **EU Withdrawals** in the WordPress admin menu to review incoming requests.
* Link the withdrawal page from your footer, terms, or order confirmation emails so customers can easily find it.

== Frequently Asked Questions ==

= Is this plugin mandatory for my store? =

If you sell goods or services to **consumers in the European Union** under distance or off-premises contracts, **Directive (EU) 2023/2673 Article 11a** requires you to provide an easy-to-use **electronic means** for customers to withdraw from the contract. This obligation applies to eligible traders regardless of whether they use WooCommerce or another platform—the plugin gives WooCommerce merchants a ready-made, auditable implementation. Consult your legal adviser for obligations specific to your business model, member state, and product categories (some exceptions apply under national transposition).

= Does it work without a customer account (guest checkout)? =

**Yes.** The withdrawal flow is fully available to guest purchasers. Customers identify themselves with their **order number** and **billing email address**—the same details provided at checkout—without needing to log in to WordPress or WooCommerce My Account.

= How does the two-step process work? =

**Step 1:** The customer enters their order details and sees the standardized withdrawal information and legal statement. **Step 2:** They review a summary of the order and must actively confirm the withdrawal request. Only after confirmation is the request persisted, logged in the audit trail, and emailed to the customer. This design supports the informed, deliberate exercise of the withdrawal right.

= How can I export data for authorities or audits? =

From **EU Withdrawals** in the WordPress admin:

* Click **Export CSV** to download withdrawal records matching your current filters (status, search, sort order).
* Click **Print view** to open a print-friendly HTML page—use your browser’s **Print → Save as PDF** to archive or share a PDF with consumer protection authorities or your legal team.

Each request detail page also shows the full **audit log** with payload and security hashes for individual record verification.

= Is the plugin compatible with WooCommerce HPOS? =

**Yes.** The plugin declares full compatibility with WooCommerce **High-Performance Order Storage** (custom order tables). Order validation, admin meta boxes, and order edit links work correctly with HPOS enabled or disabled.

= Does the plugin send confirmation emails? =

**Yes.** After a customer confirms their withdrawal, the plugin sends an HTML **confirmation email** to the billing email address on the order. The email includes the unique request UUID, submission timestamp, and withdrawal details—supporting the **durable medium** requirement for confirmation of receipt.

== Screenshots ==

1. Frontend withdrawal form — Step 1 order lookup and legal withdrawal statement displayed to the customer.
2. Admin dashboard — List of withdrawal requests with status filters, search, and CSV/print export actions.
3. Order meta box — Withdrawal requests linked to a WooCommerce order on the order edit screen.
4. Audit log details — Individual request view showing immutable HMAC audit entries and event timeline.

== Changelog ==

= 1.0.0 =
* Initial release. Fully compliant with EU Directive 2023/2673 Article 11a.

== Upgrade Notice ==

= 1.0.0 =
Initial public release. Install to add EU Art. 11a withdrawal compliance to your WooCommerce store.
