# TAIRUS Legal & Compliance Documentation
**Version:** 1.0  
**Last Updated:** 2026-07-30  
**Jurisdiction:** International (Russia-focused, EU-compliant)

---

## Overview

TAIRUS operates as a B2B commodities marketplace serving buyers, sellers, and producers across multiple jurisdictions. This document outlines legal obligations, compliance requirements, and data protection policies.

---

## 1. Terms of Service (ToS)

### 1.1 Acceptance & Binding Agreement

**Section 1 - Acceptance of Terms**

By accessing and using TAIRUS Marketplace ("Platform"), you agree to be bound by these Terms of Service, our Privacy Policy, and all applicable laws and regulations.

TAIRUS reserves the right to modify these terms at any time. Continued use of the Platform constitutes acceptance of modified terms.

**Section 2 - User Eligibility**

You represent and warrant that:
- You are at least 18 years old or legal age of majority in your jurisdiction
- You have the legal authority to enter into binding agreements
- You are not a sanctioned individual or entity (OFAC, EU, UN lists)
- You comply with all applicable export control regulations
- You are not located in embargoed countries (Iran, North Korea, Syria, Crimea, etc.)

### 1.2 User Responsibilities

**Section 3 - Account Registration**

- Provide accurate, complete, and current information
- Maintain confidentiality of login credentials
- Notify us immediately of unauthorized access
- Responsible for all activity under your account
- One account per person/company (no resale of accounts)

**Section 4 - Prohibited Conduct**

Users may not:
- Engage in money laundering or terrorist financing
- Trade in controlled commodities without proper licenses
- Misrepresent product quality or origin
- Circumvent KYC/AML verification procedures
- Violate export control regulations (e.g., Russian goods to US)
- Use platform for fraudulent transactions
- Harass, threaten, or abuse other users
- Scrape or automate platform access without permission

### 1.3 Transaction Obligations

**Section 5 - Payment & Escrow**

- Platform holds funds in escrow during transaction
- Funds released upon delivery confirmation
- Buyer has 7 days to raise disputes after delivery
- Refunds issued within 5 business days after dispute resolution
- No refund for transactions cancelled after seller starts processing

**Section 6 - Seller Obligations**

- Deliver goods matching description (quality, quantity, origin)
- Ship within agreed timeframe or notify buyer
- Provide tracking information
- Comply with destination country import regulations
- Responsible for all customs documentation

**Section 7 - Buyer Obligations**

- Pay according to agreed terms
- Accept delivery within reasonable timeframe
- Inspect goods and report issues within 7 days
- Responsible for importing/reselling compliance

### 1.4 Liability Limitations

**Section 8 - Disclaimers**

TAIRUS provides the Platform "as-is" without warranties:
- No guarantee of continuous, uninterrupted service
- No guarantee of accuracy or completeness of marketplace data
- No liability for third-party content or fraudulent users
- Platform not responsible for custom violations or import duties

**Section 9 - Liability Cap**

TAIRUS total liability to you is limited to:
- Fees paid in last 12 months, or
- $1,000 USD, whichever is greater

This applies to:
- Payment failures or lost transactions
- Data breaches (up to cap, then insurance covers)
- Service interruptions
- Disputes with other users

**Exception:** No cap on liability for:
- Data breaches affecting personally identifiable information
- Willful misconduct or gross negligence

### 1.5 Dispute Resolution

**Section 10 - Dispute Process**

1. **Direct Negotiation** (7 days)
   - Buyer/seller attempts direct resolution
   - Platform provides messaging tools

2. **Dispute Filing** (14 days after transaction)
   - File through platform with evidence
   - Platform arbitrator reviews case

3. **Platform Decision** (14 days)
   - Platform issues decision
   - Either party can appeal to external arbitration

4. **External Arbitration** (if appealed)
   - Administered by ICC (International Chamber of Commerce)
   - Binding arbitration under ICC Rules
   - Arbitrator appointed in neutral jurisdiction
   - Loser pays arbitration costs

**Section 11 - Governing Law**

- These Terms governed by laws of Delaware (incorporation jurisdiction)
- Disputes subject to arbitration, not court litigation
- Arbitration conducted in English language
- Each party bears own legal costs unless arbitrator rules otherwise

---

## 2. Privacy Policy & GDPR Compliance

### 2.1 Data Collection

**What We Collect**

1. **Identity Information**
   - Name, email, phone
   - Company name, registration number
   - Tax ID, business license
   - Beneficial owner information

2. **Verification Documents**
   - Passport/ID copy
   - Business registration certificate
   - Bank statements
   - Proof of address

3. **Transaction Data**
   - Products bought/sold
   - Prices, quantities, payment methods
   - Delivery addresses
   - Timestamps

4. **Behavioral Data**
   - Searches performed
   - Pages visited
   - Time on platform
   - Device/IP information

5. **Payment Information**
   - Bank account details (encrypted)
   - Credit card data (Stripe handles, never stored)
   - Transaction history

### 2.2 GDPR Compliance (EU Users)

**Legal Basis for Processing**

| Data | Basis | Purpose |
|------|-------|---------|
| Identity info | Contractual necessity | Account creation, service delivery |
| Verification docs | Legal obligation | KYC/AML compliance |
| Transaction data | Contractual necessity | Order processing, payments |
| Behavioral data | Legitimate interest | Fraud detection, UX improvement |
| Marketing emails | Consent | Newsletter, promotions |

**User Rights (GDPR Articles 15-22)**

- **Right of Access** (Art. 15): Request copy of your data within 30 days
- **Right to Rectification** (Art. 16): Correct inaccurate data
- **Right to Erasure** (Art. 17): Request deletion ("right to be forgotten")
  - Exception: 7-year retention for financial records (tax law)
- **Right to Restrict Processing** (Art. 18): Limit how data used
- **Right to Data Portability** (Art. 20): Export data in machine-readable format
- **Right to Object** (Art. 21): Opt-out of processing
- **Rights Related to Automated Decisions** (Art. 22): Human review of automated decisions

**Data Retention**

| Data Type | Retention Period | Reason |
|-----------|-----------------|--------|
| Transaction records | 7 years | Tax/regulatory requirement |
| User profile (if deleted) | 30 days | Data backup, recovery window |
| Payment records | 7 years | Money laundering compliance |
| Audit logs | 3 years | Dispute evidence |
| Marketing emails | 2 years | Marketing effectiveness |
| Deleted user data | Immediate | GDPR right to erasure |
| Fraud investigation data | 3 years | Prevention of repeated fraud |

**Data Sharing**

We share data with:
- **Stripe** (payment processor) - credit card data
- **Sberbank** - bank transfer verification
- **FinCEN/FATF** - AML compliance reporting
- **Customs authorities** - export control compliance
- **Tax authorities** - financial records (as required by law)
- **Law enforcement** - upon legal subpoena
- **Insurance providers** - fraud investigation

All third parties sign Data Processing Agreements (DPAs) under GDPR.

### 2.3 Data Security

**Encryption**

- **In Transit:** TLS 1.3 for all connections
- **At Rest:** AES-256 encryption for sensitive data
  - Personally identifiable info encrypted
  - Payment card data handled by PCI-compliant Stripe (not stored)
  - Passwords hashed with bcrypt (salt rounds: 12)

**Access Controls**

- Role-based access control (RBAC)
- Admin access logged and audited
- No plaintext passwords or tokens in logs
- MFA required for admin accounts
- VPN required for internal access

**Incident Response**

- Data breach investigation procedure
- GDPR breach notification within 72 hours
- User notification if personal data exposed
- Regulatory reporting to supervisory authority

---

## 3. KYC (Know Your Customer) Compliance

### 3.1 Verification Levels

**Level 1 - Basic Verification (for buyers < $50k/transaction)**

Required documents:
- [ ] Government-issued ID (passport, driver's license)
- [ ] Proof of address (utility bill, bank statement < 3 months old)
- [ ] Self-declaration of non-PEP status
- [ ] Email verification

Timeline: 2-3 business days
Fee: Free

**Level 2 - Enhanced Verification (for sellers or > $50k transactions)**

Required documents:
- [ ] All Level 1 documents
- [ ] Business registration certificate
- [ ] Tax ID certificate
- [ ] Bank statement (last 3 months)
- [ ] Beneficial ownership declaration (if company)

Timeline: 5-7 business days
Fee: $50 USD

**Level 3 - Full Due Diligence (for enterprise sellers or > $500k/year)**

Required documents:
- [ ] All Level 2 documents
- [ ] Audited financial statements (last 2 years)
- [ ] Directors/beneficial owners personal ID verification
- [ ] Reference letters from business partners
- [ ] Industry-specific certifications (if required)

Timeline: 10-15 business days
Fee: $500 USD

### 3.2 KYC Verification Process

**Step 1: Document Submission**
```
User uploads documents via secure upload (encrypted)
System performs automated checks:
- Document validity (expiration dates)
- Face recognition (if ID with photo)
- Text extraction and validation
- Duplicate detection (same person/company)
```

**Step 2: Automated Screening**
```
System checks against:
- OFAC (Office of Foreign Assets Control) - US sanctions
- EU sanctions list
- UN security council list
- FinCEN politically exposed persons (PEP) database
- World-Check database
- Internal fraud blacklist

Result: Auto-approve if clean, or flag for manual review
```

**Step 3: Manual Review**
```
For flagged applications:
- Human analyst reviews documents
- Cross-reference multiple data sources
- Interview user if needed (video call)
- Make APPROVE/REJECT/ESCALATE decision

Criteria:
- Document authenticity
- Sanctions/PEP status
- Business legitimacy
- Risk profile
- Beneficial ownership verification
```

**Step 4: Decision & Appeal**

- User notified of approval/rejection within 1 business day
- Rejection includes reason and appeal process
- Appeal reviewed by different analyst
- Appeal response within 3 business days

### 3.3 Ongoing Monitoring (Periodic Re-verification)

**Annual KYC Update**
- Re-verify all users on 1-year anniversary
- Update beneficial ownership if company structure changed
- Confirm sanctions status (monthly automated check)
- Transaction pattern analysis

**Adverse Media Monitoring**
- Automated news feed monitoring
- If mention of sanctions/fraud detected, escalate
- Monthly reports to compliance team

**Transactional Monitoring**
- Flag unusual transactions:
  - Sudden increase in volume/amount
  - Change in transaction pattern
  - High-risk countries
  - Structuring (multiple small transactions to avoid limits)

---

## 4. AML (Anti-Money Laundering) Compliance

### 4.1 Suspicious Activity Reporting (SAR)

**What Triggers SAR Filing**

Threshold: Any combination of factors totaling risk score > 70/100

Risk Factors (weighted):
- Transaction amount > $100K: +20 points
- Destination country high-risk (e.g., Syria): +30 points
- Sanctions list match: +100 (automatic SAR)
- Transaction pattern anomaly: +15 points
- New account + large transaction: +20 points
- Multiple structuring attempts: +25 points
- Known PEP involvement: +30 points

**Filing Process**

1. Automated system triggers alert
2. Compliance analyst reviews transaction
3. Decision: SAR or No SAR
4. If SAR: File with FinCEN within 30 days
5. File reports monthly to treasury

**SAR Content**
```
- Transaction details (date, amount, parties)
- Suspicious activity description
- Basis for suspicion
- Regulatory violations suspected
- Account history summary
- Related transactions
```

**Confidentiality:** SARs marked "Suspicious Activity Report - Confidential"
No customer notification (prohibited by law).

### 4.2 Cash Transaction Reporting (CTR)

**Threshold:** Any payment > $10K USD equivalent

- File with appropriate authority (FinCEN in US, FIU in Russia, etc.)
- File within 15 calendar days
- Report aggregated daily volume

### 4.3 High-Risk Country Procedures

**List of High-Risk Jurisdictions**
- Financial Action Task Force (FATF) grey list countries
- OFAC-designated countries (Iran, North Korea, Syria, etc.)
- EU High-Risk list
- Jurisdictions with weak AML/CFT regimes

**Enhanced Due Diligence (EDD) for High-Risk Countries**

If user from high-risk country:
1. Require Level 3 KYC documents
2. Quarterly re-verification
3. Source of funds declaration
4. Beneficial ownership full disclosure
5. Manual review of all transactions > $50K

**Transaction Blocking**

Certain jurisdictions blocked entirely:
- Iran
- North Korea
- Syria
- Crimea
- Other OFAC designations

Attempts to transact result in immediate account freeze.

---

## 5. Export Control Compliance

### 5.1 Restricted Commodities

**Cannot trade on TAIRUS:**
- Nuclear materials or technology
- Military weapons/explosives
- Certain chemicals (Schedule 1)
- Restricted minerals (conflict minerals)
- Endangered species/products
- Ozone-depleting substances

**Controlled commodities (require license):**
- Encryption/cryptography technology
- Advanced semiconductors
- Oil/gas (from certain origins)
- Dual-use technology (civilian + military)

### 5.2 Export Control by Origin

**Russian Commodities → Western Buyer**
```
Required Steps:
1. Check if commodity on sanctions list (2024 EU/US sanctions)
2. Check buyer not on sanctions list
3. Check buyer country not embargoed
4. Require buyer to self-certify end-use
5. Document all compliance records
6. Block if any sanctions involvement

Sanctioned items (Russia):
- Oil/gas (except specific exceptions)
- Metals (palladium, titanium, aluminum)
- Semiconductors/electronics
- Technology/software
```

**US Tech/Semiconductors → International Buyer**
```
Requires:
1. BIS license (Bureau of Industry and Security)
2. End-use certificate from buyer
3. Destination country approval
4. EAR (Export Administration Regulations) compliance
```

### 5.3 User Restrictions

**Automatic Blocking**
- User attempts to import Russian oil to US: BLOCKED
- User not listed on sanctions lists but in embargoed country: BLOCKED
- User attempting export of US semiconductors to China without license: BLOCKED

**User Declaration**
```
At transaction confirmation, user declares:
"I certify that this transaction complies with:
- Export control regulations (EAR, ITAR)
- Sanctions regulations (OFAC, EU, UN)
- Destination country import laws
- End-use certificate requirements
- I am not engaged in prohibited military/dual-use activities"

Signature required (digital signature or explicit agreement)
```

---

## 6. Consumer Protection & Escrow

### 6.1 Escrow Mechanism

**How Escrow Works**

```
1. Buyer initiates transaction
   ↓
2. TAIRUS collects payment from buyer
   ↓
3. Funds held in escrow account (separate from TAIRUS operations account)
   ↓
4. Seller notified payment received, begins processing
   ↓
5. Upon delivery:
   - Buyer receives goods
   - Buyer releases escrow (confirms receipt)
   - Seller receives payment
   
OR if dispute:
   - Buyer files claim within 7 days
   - TAIRUS arbitrates
   - Funds returned to buyer or released to seller based on decision
```

**Escrow Accounts**
- Held at licensed banks (compliance with banking regulations)
- Segregated from platform operating accounts
- Subject to audit
- Interest accrued to seller/buyer (not retained by TAIRUS)

**Escrow Insurance**
- All funds insured against bank failure
- Terrorism exclusion applies (OFAC/sanctioned entities)
- Insurance policy reviewed annually

### 6.2 Refund Policy

**Conditions for Refund**

Buyer entitled to full refund if:
- Goods not delivered by agreed deadline
- Goods damaged or non-conforming
- Seller breaches contract
- Seller becomes insolvent

Refunds NOT issued for:
- Buyer's remorse (change of mind)
- Buyer-initiated cancellation after production started
- Price changes (market conditions)
- Incorrect quantity (if buyer instructed quantity)

**Refund Timeline**
- Issued within 5 business days of approval
- Credited to original payment method
- If credit card payment: may appear 3-5 additional business days

---

## 7. Intellectual Property & Content

### 7.1 User Content License

By uploading content (product photos, descriptions, etc.), user grants TAIRUS:
- Worldwide, royalty-free license to display content
- Right to modify formatting/optimize images
- Non-exclusive (user retains ownership)
- Right to use for marketing/analytics (with anonymization)

### 7.2 Prohibited Content

Users may not post:
- Copyrighted material without authorization
- Counterfeit products
- Stolen merchandise
- Inaccurate product descriptions (misleading images)
- Adult content, hate speech, violence

### 7.3 DMCA Compliance

- TAIRUS responds to DMCA takedown notices within 24 hours
- Notices forwarded to alleged infringer
- Content removed if valid claim
- Counter-notice process available

**DMCA Agent:**
```
Name: Legal Compliance Team
Email: legal@tairus.io
Address: [Company registered address]
```

---

## 8. Regulatory Reporting

### 8.1 Anti-Terrorism Financing (ATS)

**Reporting to FATF & UNSC**

If transaction involves:
- Designated terrorist organizations
- Blocked individuals/entities
- High-risk financing patterns
- Countries/regions on terror list

→ Automatic filing with relevant authority (Treasury, FBI, etc.)

### 8.2 Tax Compliance

**1099 Reporting (US Sellers)**

TAIRUS reports to IRS:
- All US seller payments > $20K + 200 transactions/year
- Form 1099-NEC filed annually
- User receives copy by January 31st

**VAT/GST Reporting (EU)**

- TAIRUS liable for VAT on marketplace services
- Sellers may be liable for VAT on goods
- Quarterly VAT returns filed with tax authorities

### 8.3 Regulatory Filings

**Annual Reports**
- Financial statements (audited)
- AML/CFT compliance certificate
- Incident reports
- User complaint summary

**Quarterly Reports**
- Transaction volume by jurisdiction
- SAR/CTR filing summary
- New user KYC metrics
- Dispute resolution metrics

---

## 9. Incident Response & Data Breaches

### 9.1 Data Breach Response

**Timeline:**

1. **Detection (T+0 min)**
   - Automated alerts notify security team
   - Incident declared

2. **Investigation (T+1 hour)**
   - Isolate affected systems
   - Determine scope and impact
   - Identify affected users

3. **Interim Notification (T+4 hours, if ongoing incident)**
   - Notified users of breach investigation
   - No details pending investigation

4. **Regulatory Notification (T+24-72 hours)**
   - Notify supervisory authority (within 72 hours per GDPR)
   - Notify law enforcement if criminal
   - Submit SAR if AML-related

5. **User Notification (T+72 hours)**
   - Individual notification letters
   - Description of data exposed
   - Recommended actions
   - Free credit monitoring (1 year) if financial data exposed

### 9.2 Breach Response Team

- **Incident Commander:** Coordinates response
- **Security Lead:** Investigates and contains breach
- **Legal:** Regulatory notifications and litigation defense
- **Communications:** External statements and notifications
- **Forensics:** Preserves evidence for law enforcement

### 9.3 Root Cause Analysis

After 30 days: Publish incident report including
- Timeline of events
- Root cause
- Contributing factors
- Remediation implemented
- Preventive measures for future

---

## 10. Implementation Checklist

### Legal Documents ✓
- [x] Terms of Service (Section 1)
- [x] Privacy Policy (Section 2)
- [x] KYC Procedures (Section 3)
- [x] AML Procedures (Section 4)
- [x] Export Control Policy (Section 5)
- [x] Escrow & Consumer Protection (Section 6)
- [x] IP Policy (Section 7)
- [x] Regulatory Reporting (Section 8)
- [x] Incident Response (Section 9)

### Developer Implementation
- [ ] Terms acceptance flow (during signup)
- [ ] Privacy Policy link (footer + settings)
- [ ] KYC document upload UI
- [ ] Sanctions screening API integration
- [ ] Export control checks (commodity selection)
- [ ] Escrow account management
- [ ] Data deletion workflow (right to erasure)
- [ ] Incident notification system
- [ ] Audit logging for compliance

### Legal Review Required
- [ ] Local jurisdiction compliance (Russia, EU, US)
- [ ] Insurance policy (errors & omissions, cyber)
- [ ] Contracts with payment processors (DPA)
- [ ] Banking relationships (escrow accounts)

---

## 11. Compliance Calendar

| Date | Task | Owner |
|------|------|-------|
| Monthly | SAR/CTR filing review | Compliance |
| Monthly | AML monitoring reports | Compliance |
| Quarterly | Regulatory reporting | Legal |
| Quarterly | KYC re-verification (high-risk users) | Operations |
| Annually | Privacy policy audit | Legal/Data Officer |
| Annually | Security audit | Security |
| Annually | Financial audit | CFO/Auditor |
| Annually | GDPR compliance review | DPO |
| Bi-annual | Export control training | Legal |
| As-needed | Data breach response | Security/Legal |

---

**Version History:**
- v1.0 (2026-07-30): Complete legal and compliance framework including ToS, Privacy Policy, KYC/AML procedures, export control, and regulatory reporting requirements.

**Disclaimer:** This document is a template for demonstration purposes. Before deploying TAIRUS in production, consult with qualified legal counsel in relevant jurisdictions (Russia, EU, US) to ensure compliance with local regulations.
