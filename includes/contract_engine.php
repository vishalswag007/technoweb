<?php
/**
 * Vishal Web Studio - Contract Engine & Digital Signature System
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/helpers.php';

class ContractEngine {

    /**
     * Get default contract template markup with merge tags
     */
    public static function getDefaultContractTemplate(): string {
        return <<<HTML
<div class="contract-doc">
    <div class="contract-header text-center">
        <h2>WEBSITE DESIGN, DEVELOPMENT & HOSTING SERVICES AGREEMENT</h2>
        <p class="contract-meta">Contract Reference: <strong>{{contract_number}}</strong> | Date: <strong>{{date}}</strong></p>
    </div>

    <hr class="my-4">

    <div class="parties-section">
        <p>This Agreement is entered into by and between:</p>
        <p><strong>DEVELOPER / AGENCY:</strong> <em>{{developer_name}}</em> (Email: {{developer_email}}, Phone: {{developer_phone}})</p>
        <p><strong>CLIENT:</strong> <em>{{client_name}}</em> representing <em>{{business_name}}</em> (Email: {{client_email}}, Phone: {{client_phone}})</p>
    </div>

    <h4>1. PROJECT SCOPE & DELIVERABLES</h4>
    <p>Developer agrees to conceptualize, design, develop, test, and deploy a responsive website tailored for the Client's business. Deliverables include:</p>
    <ul>
        <li>Selected Package: <strong>{{package_name}}</strong></li>
        <li>Clean, modern mobile-friendly UI/UX built with high performance.</li>
        <li>Zero-Code Client Content Management Admin Dashboard.</li>
        <li>1-Click WhatsApp Direct Integration & Lead Capture Forms.</li>
        <li>Search Engine Optimization (SEO) readiness with meta descriptions and Google Maps integration.</li>
        <li>Secure SSL HTTPS Certificate and cloud server hosting setup.</li>
    </ul>

    <h4>2. FINANCIAL TERMS & PAYMENT SCHEDULE</h4>
    <p>Total Agreed Project Compensation: <strong class="text-primary">{{price}}</strong> (inclusive of applicable taxes).</p>
    <p><strong>Payment Terms:</strong> {{payment_terms}}</p>

    <h4>3. DEVELOPMENT TIMELINE</h4>
    <p>The estimated completion and deployment timeline is <strong>{{timeline}}</strong> from the date of contract signing and receipt of initial requirements/assets from the Client.</p>

    <h4>4. REVISION & APPROVAL POLICY</h4>
    <p>Client is entitled to two (2) comprehensive rounds of design reviews and revisions during the Development and Client Review stages before final production publication.</p>

    <h4>5. HOSTING, DOMAIN & MAINTENANCE</h4>
    <p>Annual domain and NVMe cloud hosting maintenance will remain active as per the selected package. Developer provides 30 days of complimentary post-launch support for bug fixes and content assistance.</p>

    <h4>6. INTELLECTUAL PROPERTY & OWNERSHIP</h4>
    <p>Upon receipt of full final payment, all custom website content, images, graphics, and text supplied or explicitly commissioned by the Client shall become the sole property of the Client.</p>

    <h4>7. ACKNOWLEDGEMENT & ACCEPTANCE</h4>
    <p>By digitally signing below, both parties confirm they have thoroughly read, understood, and agreed to all provisions and terms detailed in this Agreement.</p>
</div>
HTML;
    }

    /**
     * Replace merge tags with actual entity values
     */
    public static function compileContract(string $template, array $data): string {
        $replacements = [
            '{{contract_number}}' => $data['contract_number'] ?? generate_contract_number(),
            '{{date}}'            => $data['date'] ?? date('d M Y'),
            '{{developer_name}}'  => get_setting('business_name', APP_NAME),
            '{{developer_email}}' => get_setting('email', APP_EMAIL),
            '{{developer_phone}}' => get_setting('phone', APP_PHONE),
            '{{client_name}}'     => $data['client_name'] ?? 'Valued Client',
            '{{business_name}}'   => $data['business_name'] ?? 'Client Business',
            '{{client_email}}'    => $data['client_email'] ?? '',
            '{{client_phone}}'    => $data['client_phone'] ?? '',
            '{{package_name}}'    => $data['package_name'] ?? 'Custom Web Solution',
            '{{price}}'           => format_currency($data['price'] ?? 0),
            '{{payment_terms}}'   => $data['payment_terms'] ?? '50% advance upon contract signing, 50% prior to final live launch.',
            '{{timeline}}'        => $data['timeline'] ?? '7 to 10 business days',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Process Digital Signature submission
     */
    public static function signContract(int $contractId, array $signData): bool {
        $stmt = db()->prepare("SELECT * FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            return false;
        }

        if ($contract['status'] === 'signed') {
            return true; // Already signed and locked
        }

        $signerName  = trim($signData['signer_name'] ?? '');
        $signerEmail = trim(strtolower($signData['signer_email'] ?? ''));
        $sigMethod   = $signData['signature_method'] ?? 'draw';
        $sigData     = $signData['signature_data'] ?? '';
        $signerIp    = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (empty($signerName) || empty($signerEmail) || empty($sigData)) {
            return false;
        }

        // Generate SHA256 Integrity Hash
        $hashString = $contract['contract_content'] . '|' . $signerName . '|' . $signerEmail . '|' . $signerIp . '|' . time();
        $contractHash = hash('sha256', $hashString);

        $pdo = db();
        $isMySql = Database::getInstance()->isMySQL();
        
        $sql = "UPDATE contracts SET 
                status = 'signed', 
                signed_at = " . ($isMySql ? "NOW()" : "datetime('now')") . ", 
                signature_method = ?, 
                signature_data = ?, 
                signer_name = ?, 
                signer_email = ?, 
                signer_ip = ?, 
                contract_hash = ? 
                WHERE id = ?";

        $upd = $pdo->prepare($sql);
        $upd->execute([
            $sigMethod,
            $sigData,
            $signerName,
            $signerEmail,
            $signerIp,
            $contractHash,
            $contractId
        ]);

        // If linked to an order, update order status to contract_signed
        if (!empty($contract['order_id'])) {
            $ordUpd = $pdo->prepare("UPDATE orders SET status = 'contract_signed' WHERE id = ?");
            $ordUpd->execute([$contract['order_id']]);
        }

        log_activity(
            null,
            'contract_signed',
            'contracts',
            $contractId,
            "Contract {$contract['contract_number']} digitally signed by {$signerName} ({$signerEmail})"
        );

        return true;
    }
}
