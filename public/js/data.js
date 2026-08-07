/* ASSOCHAM Procurement ERP — static demo data + formatting helpers */

const ROLES = [
  { id: 'pr_exec', label: 'PR Senior Executive', scope: 'Creates & modifies Purchase Requisitions and Goods Receipt Notes', name: 'Priya Nair' },
  { id: 'buyer', label: 'Buyer', scope: 'Administration Team · creates RFQs, prepares CCS, creates/modifies & dispatches POs', name: 'Rohit Verma' },
  { id: 'admin_head', label: 'Admin Head', scope: 'Reviews vendor empanelment; approves non-IT POs only — cannot create POs', name: 'Rahul Mehta' },
  { id: 'asg_ch', label: 'ASG / CH', scope: 'Approver · L1 / L2 approvals across PR, Empanelment, Reimbursement & Travel', name: 'Anil Kulkarni' },
  { id: 'cfo', label: 'CFO', scope: 'L3 financial approval, PO > ₹10L sign-off, budget override authority', name: 'Sanjay Menon' },
  { id: 'sg', label: 'Secretary General', scope: 'Sole-source PR, international travel & top-level escalation approvals', name: 'Meenakshi Rao' },
];

function fmtCr(cr, currency) {
  if (currency === 'USD') { const m = (cr * 10) / 83; return '$' + m.toFixed(2) + 'M'; }
  return '₹' + cr.toFixed(1) + ' Cr';
}
function fmtCrTotal(cr, currency) {
  if (currency === 'USD') { const m = (cr * 10) / 83; return '$' + m.toFixed(1) + 'M'; }
  return '₹' + cr.toFixed(1) + ' Cr';
}

const STATUS_META = {
  'Empanelled': ['#E5F9F0', '#0bc472'],
  'L3 – CFO': ['#E6F1FE', '#0878f1'],
  'L2 – ASG': ['#FFF0E5', '#ff6f02'],
  'Under Review': ['#FFF0E5', '#ff6f02'],
  'Blacklisted': ['#FDE6E9', '#e40032'],
  'Suspended': ['#FFF0E5', '#ff6f02'],
  'Draft': ['#F4F1EE', '#7F7770'],
};
function statusMeta(s) { return STATUS_META[s] || STATUS_META['Draft']; }

const VENDORS_RAW = [
  { name: 'Bharat Infotech Systems Pvt Ltd', code: 'VND-2041', cat: 'IT & Software', pan: 'AABCB1234K', gst: '27AABCB1234K1Z5', status: 'L3 – CFO', stage: 4, av: '#0878f1', panOk: true, gstOk: true, loc: 'Mumbai, MH', msme: 'Small' },
  { name: 'Sunrise Facility Services', code: 'VND-2038', cat: 'Facility Mgmt', pan: 'AAECS9921M', gst: '07AAECS9921M1Z2', status: 'L2 – ASG', stage: 3, av: '#ff6f02', panOk: true, gstOk: true, loc: 'New Delhi, DL', msme: 'Micro' },
  { name: 'Kaveri Print & Media', code: 'VND-2035', cat: 'Printing & Media', pan: 'AAJCK4410P', gst: '29AAJCK4410P1Z9', status: 'Under Review', stage: 1, av: '#0878f1', panOk: true, gstOk: false, loc: 'Bengaluru, KA', msme: 'Micro' },
  { name: 'Orion Travel Desk LLP', code: 'VND-2030', cat: 'Travel & Events', pan: 'AAFFO7788Q', gst: '27AAFFO7788Q1Z1', status: 'Empanelled', stage: 5, av: '#0bc472', panOk: true, gstOk: true, loc: 'Pune, MH', msme: 'Small' },
  { name: 'Delta Office Supplies', code: 'VND-2027', cat: 'Stationery & Supplies', pan: 'AAGCD3312R', gst: '—', status: 'Draft', stage: 0, av: '#7F7770', panOk: true, gstOk: false, loc: 'Noida, UP', msme: 'Micro' },
  { name: 'Zenith Consulting Group', code: 'VND-2019', cat: 'Advisory & Research', pan: 'AAHCZ5567L', gst: '06AAHCZ5567L1Z8', status: 'Blacklisted', stage: 0, av: '#e40032', panOk: true, gstOk: true, loc: 'Gurugram, HR', msme: 'Medium' },
];

function vendorData() {
  return VENDORS_RAW.map((v, i) => {
    const [bg, col] = statusMeta(v.status);
    return {
      ...v, idx: i,
      initials: v.name.split(' ').slice(0, 2).map(w => w[0]).join(''),
      panShort: v.pan.slice(0, 4) + '…',
      gstShort: v.gst === '—' ? '—' : v.gst.slice(0, 4) + '…',
      panDotColor: v.panOk ? '#2E7D4F' : '#C77A00',
      gstDotColor: v.gstOk ? '#2E7D4F' : '#C77A00',
      avatarBg: v.av, pillBg: bg, pillColor: col,
    };
  });
}

function buildDetail(v) {
  const stageDefs = [
    { level: 'Submit', title: 'Buyer Submission', role: 'Administration Team (Buyer) · registration submitted' },
    { level: 'Review', title: 'Admin Head Review', role: 'Admin Head · completeness check + auto PAN & GST validation' },
    { level: 'L1', title: 'ASG-1 Approval', role: 'Assistant Secretary General 1' },
    { level: 'L2', title: 'ASG-2 Approval', role: 'Assistant Secretary General 2' },
    { level: 'L3', title: 'CFO Final Approval', role: 'Chief Financial Officer' },
  ];
  const meta = ['Submitted · 10 Jul, R. Verma (Buyer)', 'Reviewed · 12 Jul, R. Mehta — PAN ✓ GST ✓', 'Approved · 15 Jul, A. Kulkarni', 'Pending review', 'Awaiting L2 completion'];
  const stages = stageDefs.map((s, i) => {
    let st;
    if (v.stage >= 5) st = 'done';
    else if (i < v.stage) st = 'done';
    else if (i === v.stage) st = 'active';
    else st = 'pending';
    if (v.status === 'Blacklisted') st = i === 0 ? 'reject' : 'pending';
    const colors = { done: '#0bc472', active: '#ff6f02', pending: '#D8CFC7', reject: '#e40032' };
    const metaColor = { done: '#0bc472', active: '#ff6f02', pending: '#B0A69D', reject: '#e40032' }[st];
    const metaBg = { done: '#E5F9F0', active: '#FFF0E5', pending: '#F4EEE8', reject: '#FDE6E9' }[st];
    return {
      ...s, st,
      icon: st === 'done' ? '✓' : st === 'reject' ? '✕' : String(i + 1),
      nodeColor: colors[st],
      hasMeta: st !== 'pending',
      meta: (v.status === 'Blacklisted' && i === 0) ? 'Rejected · compliance flag' : meta[i],
      metaColor, metaBg,
      connectorColor: st === 'done' ? '#B7DCC5' : '#EEE7E0',
      isLast: i === stageDefs.length - 1,
    };
  });

  const badge = (ok, yes, no) => ({ hasBadge: true, badge: ok ? yes : no, ok });
  const sections = [
    { title: 'Personal / Firm Details', fields: [
      { label: 'Name / Entity', value: v.name },
      { label: 'Contact Person', value: v.contact || 'Ravi Kumar (Director)' },
      { label: 'Government ID Number', value: v.govId || v.pan },
      { label: 'Registered Address', value: v.loc },
      { label: 'Mobile Number', value: v.mobile || '+91 98••• ••210' },
      { label: 'Email ID', value: v.email || ('accounts@' + v.code.toLowerCase() + '.in') },
    ] },
    { title: 'Banking Details', fields: [
      { label: 'Bank Name', value: v.bankName || 'HDFC Bank' },
      { label: 'Branch Address', value: v.bankBranch || v.loc },
      { label: 'Account Holder Name', value: v.bankAccHolder || v.name },
      { label: 'Account Number', value: v.bankAccNum || '•••• •••• 4471' },
      { label: 'Account Type', value: v.bankAccType || 'Current' },
      { label: 'IFSC Code', value: v.bankIfsc || 'HDFC0001234' },
    ] },
    { title: 'Business & References', fields: [
      { label: 'Business Category (deals in)', value: v.cat },
      { label: 'MSME Classification', value: (v.msme || 'Micro') + ' Enterprise' },
      { label: 'Reference 1', value: v.ref1 || 'NITI Aayog · verified' },
      { label: 'Reference 2', value: v.ref2 || 'CII Secretariat · verified' },
    ] },
    { title: 'Tax & Statutory Details', fields: [
      { label: 'PAN Card', value: v.pan, ...badge(v.panOk, 'Valid', 'Format error') },
      { label: 'GST Registration', value: v.gst, ...badge(v.gstOk, 'Active', 'Pending') },
      { label: 'MSME / Udyam Number', value: v.msmeNum || 'UDYAM-MH-03-0042189' },
      { label: 'Incorporation Certificate', value: v.incorpCert || 'On file' },
      { label: 'Partnership Deed', value: v.partnershipDeed || 'On file' },
    ] },
  ];

  const declaration = [
    'I am not involved in any fraudulent or corrupt activities and has not been in the past and is not currently under any investigation for any such activities which would render by me unsuitable for business dealing with ASSOCHAM.',
    'I am not on, or associated with a company or individual, groups, undertakings and entities that are on the consolidated list established and maintained by the committee established by ASSOCHAM.',
    'I am not currently removed, invalidated, or suspended by any other Agency.',
    'I certifies that it has not knowingly provided and will not knowingly provide, in violation of applicable laws, material support or resources to any individual or organization that advocates, plans, sponsors, engages in, or are or has engaged in an act of terrorism.',
    'I am not related to any employee/consultant at ASSOCHAM.',
    'I do hereby declare that there is no child labour engaged in our establishment.'
  ];

  const documents = [
    { name: 'Government-issued ID (Aadhaar / Passport / PAN / DL)', ext: 'PDF', meta: v.govId ? 'gov_id.pdf · 1.2 MB' : 'gov_id.pdf · 1.1 MB', state: 'Verified', ok: true },
    { name: 'Cancelled Cheque / Bank Statement', ext: 'JPG', meta: v.bankIfsc ? 'bank_proof.jpg · 980 KB' : 'bank_proof.jpg · 820 KB', state: (v.gstOk || v.bankIfsc) ? 'Verified' : 'Pending', ok: (v.gstOk || !!v.bankIfsc) },
    { name: 'PAN Card Copy', ext: 'PDF', meta: 'pan_card.pdf · 340 KB', state: 'Verified', ok: true },
    { name: 'GST Registration Letter', ext: 'PDF', meta: v.gst && v.gst !== '—' ? 'gst_letter.pdf · 512 KB' : '—', state: (v.gst && v.gst !== '—') ? 'Verified' : 'Missing', ok: (v.gst && v.gst !== '—') },
    { name: 'MSME / Udyam Registration', ext: 'PDF', meta: v.msmeNum ? 'udyam_cert.pdf · 710 KB' : 'udyam_cert.pdf · 640 KB', state: 'Verified', ok: true },
    { name: 'Incorporation Certificate', ext: 'PDF', meta: v.incorpCert ? 'incorp.pdf · 1.4 MB' : 'incorp.pdf · 1.2 MB', state: 'Verified', ok: true },
    { name: 'Partnership Deed', ext: 'PDF', meta: v.partnershipDeed ? 'partnership_deed.pdf · 1.8 MB' : '—', state: v.partnershipDeed ? 'Verified' : 'Missing', ok: !!v.partnershipDeed },
  ];

  const audit = v.customAudit || [
    { actor: 'R. Verma (Buyer)', action: 'created the vendor record and submitted for review.', time: '10 Jul 2026, 09:14', ip: '10.2.14.7', dot: '#2F6DB0' },
    { actor: 'System', action: 'validated PAN checksum — result: PASS.', time: '10 Jul 2026, 09:15', ip: 'auto', dot: '#0bc472' },
    { actor: 'System', action: v.gstOk ? 'verified GSTIN on GSTN — status ACTIVE.' : 'flagged GSTIN — verification pending.', time: '10 Jul 2026, 09:15', ip: 'auto', dot: v.gstOk ? '#0bc472' : '#ff6f02' },
    { actor: 'R. Mehta (Admin Head)', action: 'reviewed for completeness, edited “Registered Office” and advanced to L1.', time: '12 Jul 2026, 11:02', ip: '10.2.14.7', dot: '#ff6f02' },
    { actor: 'A. Kulkarni (ASG-1)', action: 'approved at Level 1 with comment.', time: '15 Jul 2026, 16:40', ip: '10.2.9.31', dot: '#0bc472' },
  ];

  const comments = [
    { name: 'A. Kulkarni', initials: 'AK', bg: '#ff6f02', time: '15 Jul, 16:40', text: 'Documentation is complete and statutory checks are clean. Approving at L1 — routing to ASG-2 (L2), then CFO for final sign-off.' },
    { name: 'R. Mehta', initials: 'RM', bg: '#2F6DB0', time: '12 Jul, 11:05', text: 'Admin Head review complete. GST certificate re-uploaded after correction. PAN and bank proof verified.' },
  ];

  const [bg, col] = statusMeta(v.status);
  return { ...v, pillBg: bg, pillColor: col, stages, sections, declaration, documents, audit, comments,
    signedBy: v.signatoryName ? `${v.signatoryName} (${v.signatoryTitle || 'Authorized Signatory'})` : ('Authorised Signatory, ' + v.name),
    signedDate: v.signedDate || '10 Jul 2026' };
}

const MODULE_FORMS = {
  requisition: { excel: true, fields: [
    { l: 'Requisition Title', p: 'e.g. Annual laptop refresh', w: true },
    { l: 'Department', p: 'Select department' },
    { l: 'Budget Head', p: 'e.g. IT Capex FY26' },
    { l: 'Required By', p: 'DD / MM / YYYY' },
    { l: 'Estimated Value (₹)', p: '0.00' },
    { l: 'GST %', p: 'e.g. 18' },
    { l: 'Justification / Purpose', p: 'Describe the need and expected outcome', a: true },
  ] },
  rfq: { fields: [
    { l: 'Linked Purchase Requisition', p: 'Select an approved PR' },
    { l: 'Response Deadline', p: 'DD / MM / YYYY' },
    { l: 'Requirement Summary', p: 'What is being sourced', w: true },
    { l: 'Invite Vendors (empanelled only)', p: 'Search empanelled, non-blacklisted vendors', w: true },
    { l: 'Terms & Conditions (per vendor)', p: 'Payment, delivery, warranty, penalties…', a: true },
  ] },
  po: { fields: [
    { l: 'Linked Purchase Requisition', p: 'Select PR' },
    { l: 'Awarded Vendor', p: 'Select empanelled vendor (L1)' },
    { l: 'RFQ Reference', p: 'e.g. RFQ-3307' },
    { l: 'Order Value (₹)', p: '0.00' },
    { l: 'Delivery Date', p: 'DD / MM / YYYY' },
    { l: 'Payment Terms', p: 'e.g. 30 days net' },
    { l: 'Special Instructions', p: 'Conditions carried forward from RFQ', a: true },
  ] },
  grn: { uploads: ['Delivery Challan', 'Tax Invoice', 'Inspection Report'], fields: [
    { l: 'Against PR / PO', p: 'Select originating PR & PO' },
    { l: 'Invoice Number', p: 'e.g. INV-4471' },
    { l: 'Ordered Quantity', p: '0' },
    { l: 'Received Quantity', p: '0' },
    { l: 'Receipt Date', p: 'DD / MM / YYYY' },
    { l: 'Inspected By', p: 'Name of inspector' },
    { l: 'Quality Remarks', p: 'Condition, shortfalls, rejections…', a: true },
  ] },
  trf: { fields: [
    { l: 'Traveller Name', p: 'Full name' },
    { l: 'Department', p: 'Select department' },
    { l: 'Travel Type', p: 'Domestic / International' },
    { l: 'Destination', p: 'City / country' },
    { l: 'Multiple Locations (if any)', p: 'List additional cities on the itinerary', w: true },
    { l: 'Mode of Travel', p: 'Air / Rail / Road' },
    { l: 'Departure Date', p: 'DD / MM / YYYY' },
    { l: 'Return Date', p: 'DD / MM / YYYY' },
    { l: 'Flight Dates', p: 'DD/MM/YYYY – DD/MM/YYYY' },
    { l: 'Hotel Dates', p: 'DD/MM/YYYY – DD/MM/YYYY' },
    { l: 'Local Transport Dates', p: 'DD/MM/YYYY – DD/MM/YYYY' },
    { l: 'Per Diem Dates', p: 'DD/MM/YYYY – DD/MM/YYYY' },
    { l: 'Estimated Cost (₹)', p: '0.00' },
    { l: 'Advance Required (₹)', p: '0.00' },
    { l: 'Purpose of Travel', p: 'Business justification', a: true },
  ] },
  flight: { fields: [
    { l: 'Against TRF', p: 'Select approved TRF' },
    { l: 'Traveller', p: 'Name' },
    { l: 'Route / Flight', p: 'e.g. DEL → BOM' },
    { l: 'Travel Date', p: 'DD / MM / YYYY' },
    { l: 'Hotel / Stay', p: 'Property & nights' },
    { l: 'Ground Transport', p: 'Cab / self / none' },
    { l: 'Travel Vendor (empanelled)', p: 'Select empanelled travel vendor', w: true },
  ] },
  reimb: { uploads: ['Boarding Passes / Tickets', 'Hotel Bills', 'Local Conveyance Bills'], fields: [
    { l: 'Against TRF', p: 'Select TRF' },
    { l: 'Traveller', p: 'Name' },
    { l: 'Advance Taken (₹)', p: '0.00' },
    { l: 'Total Claim (₹)', p: '0.00' },
    { l: 'Remarks', p: 'Notes for finance', a: true },
  ] },
  traveleval: { fields: [
    { l: 'Vendor', p: 'Select travel vendor' },
    { l: 'Category', p: 'Agency / Hotel / Transport' },
    { l: 'Evaluation Period', p: 'e.g. Q2 FY26' },
    { l: 'Cost Competitiveness (1-5)', p: '0' },
    { l: 'Punctuality (1-5)', p: '0' },
    { l: 'Service Quality (1-5)', p: '0' },
    { l: 'Compliance (1-5)', p: '0' },
    { l: 'Overall Remarks', p: 'Summary & recommendation', a: true },
  ] },
  grievance: { fields: [
    { l: 'Grievance Subject', p: 'e.g. Delayed payment for PO-19201', w: true },
    { l: 'Related Module', p: 'Select module (e.g. Purchase Order, Travel, Vendor Empanelment)' },
    { l: 'Escalation Stage / Assigned To', p: 'Select stage (Admin Head / CFO / Secretary General)' },
    { l: 'Grievance Details', p: 'Explain the issue in detail', a: true },
  ] },
};

function formatCurrentDate() {
  const d = new Date();
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const day = d.getDate();
  const month = months[d.getMonth()];
  const year = d.getFullYear();
  const hrs = String(d.getHours()).padStart(2, '0');
  const mins = String(d.getMinutes()).padStart(2, '0');
  return `${day} ${month} ${year}, ${hrs}:${mins}`;
}

const MODULE_ROWS = {
  onboarding: [
    ['ONB-4471', 'Bharat Infotech Systems', 'ravi@bharatinfotech.in', '12 Jul 2026', 'Submitted', '#E6F1FE', '#0878f1'],
    ['ONB-4468', 'Sunrise Facility Services', 'ops@sunrisefm.in', '11 Jul 2026', 'Docs pending', '#FFF0E5', '#ff6f02'],
    ['ONB-4462', 'Kaveri Print & Media', 'hello@kaveri.co', '09 Jul 2026', 'Submitted', '#E6F1FE', '#0878f1'],
    ['ONB-4455', 'Delta Office Supplies', 'sales@deltaoffice.in', '07 Jul 2026', 'Draft', '#F4F1EE', '#7F7770'],
    ['ONB-4450', 'Nimbus Cloud Services', 'contracts@nimbus.io', '05 Jul 2026', 'Sent for empanelment', '#FFF0E5', '#ff6f02'],
  ],
  requisition: [
    ['PR-8842', 'Annual laptop refresh – 40 units', 'IT', '₹28.0 L', 'L2 · Finance', '#FFF0E5', '#ff6f02'],
    ['PR-8839', 'Conference AV upgrade', 'Events', '₹12.5 L', 'Approved', '#E5F9F0', '#0bc472'],
    ['PR-8835', 'Office stationery Q3', 'Admin', '₹3.2 L', 'Approved', '#E5F9F0', '#0bc472'],
    ['PR-8830', 'Research subscriptions', 'Policy', '₹8.9 L', 'L3 · Director', '#E6F1FE', '#0878f1'],
    ['PR-8821', 'Housekeeping contract renewal', 'Facilities', '₹15.0 L', 'Over budget', '#FDE6E9', '#e40032'],
  ],
  rfq: [
    ['RFQ-3310', 'PR-8842', '40 business laptops', '22 Jul 2026', 'Open', '#E6F1FE', '#0878f1'],
    ['RFQ-3307', 'PR-8839', 'AV equipment package', '19 Jul 2026', 'Evaluating', '#FFF0E5', '#ff6f02'],
    ['RFQ-3301', 'PR-8821', 'Annual housekeeping', '15 Jul 2026', 'Awarded', '#E5F9F0', '#0bc472'],
    ['RFQ-3298', 'PR-8815', 'Print & branding', '14 Jul 2026', 'Closed', '#F4F1EE', '#7F7770'],
  ],
  po: [
    ['PO-19204', 'Orion Travel Desk LLP', 'PR-8830', '₹11.2 L', 'Dispatched', '#E6F1FE', '#0878f1'],
    ['PO-19201', 'Sunrise Facility Services', 'PR-8821', '₹15.0 L', 'Approved', '#E5F9F0', '#0bc472'],
    ['PO-19198', 'Bharat Infotech Systems', 'PR-8842', '₹28.0 L', 'Pending (edited)', '#FFF0E5', '#ff6f02'],
    ['PO-19190', 'Kaveri Print & Media', 'PR-8815', '₹3.4 L', 'Delivered', '#E5F9F0', '#0bc472'],
  ],
  grn: [
    ['GRN-7712', 'PR-8815 / PO-19190', 'INV-4471', '40 / 40', 'Accepted', '#E5F9F0', '#0bc472'],
    ['GRN-7709', 'PR-8830 / PO-19204', '—', '0 / 12', 'Awaiting', '#FFF0E5', '#ff6f02'],
    ['GRN-7705', 'PR-8809 / PO-19185', 'INV-4460', '18 / 25', 'Partial', '#FFF0E5', '#ff6f02'],
    ['GRN-7701', 'PR-8802 / PO-19180', 'INV-4452', '30 / 30', 'Rejected', '#FDE6E9', '#e40032'],
  ],
  trf: [
    ['TRF-5521', 'P. Nair · Policy', 'Geneva (Intl)', '₹4.8 L', 'SG approval', '#E6F1FE', '#0878f1'],
    ['TRF-5518', 'A. Sharma · Events', 'Mumbai', '₹0.9 L', 'Approved', '#E5F9F0', '#0bc472'],
    ['TRF-5514', 'M. Iyer · Admin', 'Chennai', '₹0.6 L', 'L1 pending', '#FFF0E5', '#ff6f02'],
    ['TRF-5509', 'R. Mehta · Finance', 'New Delhi', '₹0.3 L', 'Approved', '#E5F9F0', '#0bc472'],
    ['TRF-5502', 'S. Menon · Mgmt', 'Singapore (Intl)', '₹6.2 L', 'Returned', '#FDE6E9', '#e40032'],
  ],
  flight: [
    ['BKG-3390', 'P. Nair', 'DEL → GVA · Hotel Metropole', '24 Jul 2026', 'Confirmed', '#E5F9F0', '#0bc472'],
    ['BKG-3387', 'A. Sharma', 'DEL → BOM · return', '19 Jul 2026', 'In transit', '#E6F1FE', '#0878f1'],
    ['BKG-3383', 'M. Iyer', 'MAA · 2 nights', '21 Jul 2026', 'To be booked', '#FFF0E5', '#ff6f02'],
    ['BKG-3378', 'S. Menon', 'DEL → SIN · Marina stay', '28 Jul 2026', 'Rescheduled', '#FFF0E5', '#ff6f02'],
  ],
  reimb: [
    ['CLM-2214', 'A. Sharma', '₹0.5 L / ₹0.82 L', '₹0.32 L', 'Awaiting finance', '#FFF0E5', '#ff6f02'],
    ['CLM-2210', 'R. Mehta', '₹0.3 L / ₹0.26 L', '−₹0.04 L', 'Settled', '#E5F9F0', '#0bc472'],
    ['CLM-2205', 'M. Iyer', '₹0.4 L / ₹0.55 L', '₹0.15 L', 'Query raised', '#FFF0E5', '#ff6f02'],
    ['CLM-2199', 'P. Nair', '₹4.0 L / ₹4.6 L', '₹0.6 L', 'Awaiting finance', '#FFF0E5', '#ff6f02'],
  ],
  traveleval: [
    ['Orion Travel Desk LLP', 'Travel Agency', '142', '4.6', 'Preferred', '#E5F9F0', '#0bc472'],
    ['SkyRoute Corporate', 'Travel Agency', '88', '4.1', 'Active', '#E6F1FE', '#0878f1'],
    ['Metropole Hotels', 'Accommodation', '51', '3.9', 'Active', '#E6F1FE', '#0878f1'],
    ['QuickCab Fleet', 'Ground Transport', '63', '2.8', 'Under watch', '#FFF0E5', '#ff6f02'],
  ],
};

function moduleConfig(currency) {
  return {
    onboarding: {
      title: 'Vendor Onboarding', cta: 'New Vendor',
      desc: 'Initiated only by the Buyer (Administration Team). Capture vendor registration details with PAN & GST format validation at entry, and upload of registration, MSME and bank proof documents.',
      stats: [['32', 'Draft applications', '#7F7770'], ['18', 'Submitted', '#0878f1'], ['11', 'Sent for empanelment', '#ff6f02'], ['248', 'Onboarded to date', '#0bc472']],
      tableTitle: 'Onboarding Applications', columns: ['REF', 'Vendor', 'Contact', 'Submitted', 'Status'],
      rows: MODULE_ROWS.onboarding,
      note: 'PAN & GST are validated for format at data entry; failed formats block submission.',
    },
    requisition: {
      title: 'Purchase Requisition', cta: 'Raise Requisition',
      desc: 'Created by designated Admin Team members (not Admin Head). Enter requisitions manually or bulk-import from Excel. Each PR is validated against the department budget (GST-inclusive), approved both budget-wise and line-item wise — partial approval of selected line items is permitted — before routing through the approval chain, with a digital-signature trail at every stage.',
      stats: [['9', 'Awaiting approval', '#ff6f02'], ['24', 'Approved this month', '#0bc472'], ['3', 'Returned', '#e40032'], [fmtCrTotal(6.2, currency), 'Requisitioned value', '#0878f1']],
      tableTitle: 'Requisitions', columns: ['REF', 'Title', 'Department', 'Value (GST-incl.)', 'Status'],
      rows: MODULE_ROWS.requisition,
      note: 'Budget check is GST-inclusive — the ERP blocks new PRs once the department budget is exhausted (CFO may override, with the reason, approver and revised budget logged). Chain: Admin Team → L1 (ASG/CH) → L2 (ASG/CH) → CFO (L3). Sole Source Procurement PRs additionally require Secretary General approval; the PR reaches the Administration Team only once every line item is approved.',
    },
    rfq: {
      title: 'RFQ & Quotation Management', cta: 'Create RFQ',
      desc: 'Created only by the Buyer, and raised only against an approved Purchase Requisition, keeping every quotation request traceable to a sanctioned need. Sent to multiple approved vendors with defined response email IDs, a standard RFQ format and standard Terms & Conditions attached per vendor; all edits are captured in the audit log.',
      stats: [['6', 'Open RFQs', '#ff6f02'], ['21', 'Quotes received', '#0878f1'], ['4', 'Under evaluation', '#ff6f02'], ['12', 'Awarded', '#0bc472']],
      tableTitle: 'Request for Quotations', columns: ['RFQ', 'Linked PR', 'Requirement', 'Closes', 'Status'],
      rows: MODULE_ROWS.rfq,
      note: 'RFQs can only be raised against an approved PR. For Rate Agreement Vendors, an RFQ is not mandatory — the Buyer may create a PO directly from the approved PR. Per-vendor T&C attachments and every edit are recorded in the audit trail.',
    },
    po: {
      title: 'Purchase Orders', cta: 'Create PO',
      desc: 'Created and modified only by the Buyer — Admin Head does not create POs. Generated against the selected vendor and linked PR, with RFQ attachments carried forward, and routed Buyer → Admin Head (non-IT items only) → CFO (orders over ₹10 Lakhs) → Secretary General (where applicable); any post-approval edit resets the PO to Pending.',
      stats: [['7', 'Pending approval', '#ff6f02'], ['31', 'Active POs', '#0878f1'], [fmtCrTotal(4.6, currency), 'Committed value', '#0bc472'], ['2', 'On hold', '#ff6f02']],
      tableTitle: 'Purchase Orders', columns: ['PO No.', 'Vendor', 'Linked PR', 'Value', 'Status'],
      rows: MODULE_ROWS.po,
      note: 'Approval authority is auto-selected by order value (thresholds finalized by CFO); Admin Head approves non-IT items and services only. Editing an approved PO resets it to Pending for re-approval. New POs are blocked once the linked budget is exhausted, unless overridden by the CFO with a logged reason.',
    },
    grn: {
      title: 'Goods Receipt Note', cta: 'Create GRN',
      desc: 'Preferably raised by the same user who created the PR, or another user from the same department — responsibility can be transferred with IT approval. Captures received quantity, invoice number and quality remarks against the originating PR and PO. Once submitted, only quantity and date fields remain editable — all other details are locked.',
      stats: [['4', 'Awaiting inspection', '#ff6f02'], ['16', 'Accepted', '#0bc472'], ['2', 'Partially received', '#ff6f02'], ['1', 'Rejected', '#e40032']],
      tableTitle: 'Goods Receipt Notes', columns: ['GRN No.', 'Against PR / PO', 'Invoice No.', 'Received qty', 'Status'],
      rows: MODULE_ROWS.grn,
      note: 'GRN approval is done by the respective Department Head after verifying quantity and quality. After submission, only quantity and date are editable — every other field is locked. Accepted GRNs release the invoice for three-way matching.',
    },
    trf: {
      title: 'Travel Requisition (TRF)', cta: 'New TRF',
      desc: 'Raise travel requests — purpose, itinerary, cost estimate and advance — capturing activity-wise dates for flights, hotels, local transport, per diem and multiple locations, with advance released, pending settlement and balance payable/receivable tracked throughout.',
      stats: [['7', 'Pending approval', '#ff6f02'], ['19', 'Approved', '#0bc472'], ['3', 'Returned', '#e40032'], ['₹18.4 L', 'Estimated cost', '#0878f1']],
      tableTitle: 'Travel Requisitions', columns: ['TRF', 'Traveller', 'Destination', 'Est. cost', 'Status'],
      rows: MODULE_ROWS.trf,
      note: 'Domestic travel (Road / Rail / Air) needs only ASG approval, except employees reporting directly to SG who require SG approval. International travel requires mandatory SG approval for anyone not reporting directly to SG. New TRFs are blocked once the travel budget is exhausted, unless the CFO overrides with a logged reason.',
    },
    flight: {
      title: 'Flight & Boarding Logistics', cta: 'New Booking',
      desc: 'Created and initiated only by the designated Administration Representative responsible for travel management. Coordinate ticketing, hotel and ground transport against approved TRFs through empanelled travel vendors, with live itinerary and status tracking.',
      stats: [['5', 'To be booked', '#ff6f02'], ['12', 'Confirmed', '#0bc472'], ['4', 'In transit', '#0878f1'], ['2', 'Rescheduled', '#ff6f02']],
      tableTitle: 'Bookings', columns: ['Booking', 'Traveller', 'Route / Stay', 'Travel date', 'Status'],
      rows: MODULE_ROWS.flight,
      note: 'Bookings are routed only to empanelled travel vendors selected via RFQ.',
    },
    reimb: {
      title: 'Reimbursement & Advance Settlement', cta: 'New Claim',
      desc: 'Reconcile travel advances against actual expenses and capture bills. The respective ASG/CH approves first; the CFO then approves only after verifying policy compliance and supporting documents. Where the claimant is themself an ASG or CH, the claim is instead routed to the Secretary General for approval.',
      stats: [['6', 'Awaiting finance', '#ff6f02'], ['14', 'Settled', '#0bc472'], ['3', 'Query raised', '#ff6f02'], ['₹4.2 L', 'Pending settlement', '#0878f1']],
      tableTitle: 'Claims & Settlements', columns: ['Claim', 'Traveller', 'Advance / Claim', 'Net payable', 'Status'],
      rows: MODULE_ROWS.reimb,
      note: 'Claims must be filed within 15 days of trip completion; advances auto-adjust against approved bills. New claims are blocked once the budget is exhausted, unless the CFO overrides with a logged reason.',
    },
    traveleval: {
      title: 'Travel Vendor Evaluation', cta: 'New Evaluation',
      desc: 'Score empanelled travel agencies and hotels on cost, punctuality, service and compliance to inform future empanelment and re-empanelment.',
      stats: [['9', 'Active vendors', '#0878f1'], ['4.3', 'Avg. score / 5', '#0bc472'], ['2', 'Under watch', '#ff6f02'], ['1', 'Flagged', '#e40032']],
      tableTitle: 'Vendor Scorecards', columns: ['Vendor', 'Category', 'Bookings', 'Score', 'Status'],
      rows: MODULE_ROWS.traveleval,
      note: 'Vendors scoring below 3.0 over two quarters are placed under watch for re-empanelment review.',
    },
  };
}

const EMPANEL_STATS = [
  { label: 'Awaiting L1', count: '2', color: '#7F7770' },
  { label: 'At L2 (ASG)', count: '2', color: '#ff6f02' },
  { label: 'At L3 (CFO)', count: '1', color: '#0878f1' },
  { label: 'Empanelled', count: '248', color: '#0bc472' },
  { label: 'Blacklisted', count: '3', color: '#e40032' },
];

const NOTIFICATIONS = [
  { text: 'PR-8842 requires your L2 approval — Annual laptop refresh (₹28.0 L).', time: '14 Jul 2026 · 11:08', glyph: '3', iconBg: '#FFF0E5', iconColor: '#ff6f02', unread: true, bg: '#FFFCF7' },
  { text: 'Bharat Infotech Systems is awaiting CFO final sign-off at L3.', time: '13 Jul 2026 · 09:40', glyph: '2', iconBg: '#FFF0E5', iconColor: '#ff6f02', unread: true, bg: '#FFFCF7' },
  { text: 'GRN-7701 was rejected on quality inspection. Vendor notified.', time: '13 Jul 2026 · 17:41', glyph: '6', iconBg: '#FDE6E9', iconColor: '#e40032', unread: true, bg: '#FFFCF7' },
  { text: 'RFQ-3301 has been awarded to Sunrise Facility Services.', time: '12 Jul 2026 · 15:02', glyph: '4', iconBg: '#E7F0FA', iconColor: '#2F6DB0', unread: false, bg: '#fff' },
];

const AUDIT_ROWS = [
  { time: '15 Jul 2026, 16:52', user: 'S. Menon', role: 'CFO', action: 'Approved empanelment (L3) — Orion Travel Desk LLP', module: 'Empanelment' },
  { time: '15 Jul 2026, 16:40', user: 'A. Kulkarni', role: 'ASG-1', action: 'Approved vendor at Level 2 with comment', module: 'Empanelment' },
  { time: '15 Jul 2026, 14:20', user: 'P. Nair', role: 'Procurement', action: 'Floated RFQ-3310 to 5 empanelled vendors', module: 'RFQ' },
  { time: '14 Jul 2026, 11:08', user: 'P. Nair', role: 'Procurement', action: 'Raised requisition PR-8842 (₹28.0 L)', module: 'Requisition' },
  { time: '13 Jul 2026, 17:41', user: 'M. Iyer', role: 'Store Officer', action: 'Rejected GRN-7701 — quality inspection failed', module: 'Goods Receipt' },
  { time: '13 Jul 2026, 09:15', user: 'System', role: 'Automated', action: 'PAN & GST verification PASS for VND-2041', module: 'Empanelment' },
  { time: '12 Jul 2026, 10:15', user: 'R. Mehta', role: 'Administrator', action: 'Set Zenith Consulting Group status = Blacklisted', module: 'Empanelment' },
  { time: '12 Jul 2026, 11:02', user: 'R. Mehta', role: 'Administrator', action: 'Edited field “Registered Office” on VND-2041', module: 'Empanelment' },
];

const CCS_COMP_META = { ok: ['#E5F9F0', '#0bc472'], partial: ['#FFF0E5', '#ff6f02'], rej: ['#FDE6E9', '#e40032'] };
const CCS_ROWS_RAW = [
  { vendor: 'Bharat Infotech Systems', amount: '₹12.4 L', delivery: '3 weeks', compliance: 'Full', docs: '4 docs', remarks: 'Meets all specs; 3-yr on-site warranty', comp: 'ok', l1: true },
  { vendor: 'SkyRoute Corporate', amount: '₹11.9 L', delivery: '5 weeks', compliance: 'Partial', docs: '3 docs', remarks: 'Missing OEM authorisation letter', comp: 'partial', l1: false },
  { vendor: 'Metropole Systems', amount: '₹13.1 L', delivery: '2 weeks', compliance: 'Full', docs: '4 docs', remarks: 'Fastest delivery; higher unit cost', comp: 'ok', l1: false },
  { vendor: 'Delta Office Supplies', amount: '₹11.2 L', delivery: '6 weeks', compliance: 'Non-compliant', docs: '2 docs', remarks: 'GST certificate expired — disqualified', comp: 'rej', l1: false },
];
const CCS_STATUS = {
  rfq: 'RFQ-3307',
  pr: 'PR-8839',
  requirement: 'AV equipment package — 5 conference rooms',
  committeeStatus: 'Pending Procurement Committee Approval'
};

function ccsData() {
  return {
    rfq: CCS_STATUS.rfq,
    pr: CCS_STATUS.pr,
    requirement: CCS_STATUS.requirement,
    closed: '19 Jul 2026',
    quotes: '4 of 4 received',
    preparedBy: 'R. Verma (Buyer)',
    committeeStatus: CCS_STATUS.committeeStatus,
    rows: CCS_ROWS_RAW.map(r => {
      const [bg, color] = CCS_COMP_META[r.comp];
      return { ...r, pillBg: bg, pillColor: color, initials: r.vendor.split(' ').slice(0, 2).map(w => w[0]).join('') };
    }),
  };
}

const MODULE_IDS = ['onboarding', 'requisition', 'rfq', 'po', 'grn', 'trf', 'flight', 'reimb', 'traveleval'];

function dashboardData(currency) {
  const kpiRaw = [
    { label: 'Total Procurement Spend (FY)', value: fmtCrTotal(42.8, currency), delta: '+8.4%', sub: 'vs last FY', up: true,
      icon: 'bi-currency-dollar', iconBg: '#f0f2fa', iconColor: '#363f7e' },
    { label: 'Empanelled Vendors', value: '248', delta: '+12', sub: 'this quarter', up: true,
      icon: 'bi-people', iconBg: '#f0f2fa', iconColor: '#363f7e' },
    { label: 'Pending Approvals', value: '17', delta: '−3', sub: 'from yesterday', up: false,
      icon: 'bi-clock', iconBg: '#f0f2fa', iconColor: '#363f7e' },
    { label: 'On-time Delivery Rate', value: '94.2%', delta: '+2.1%', sub: 'overall compliance', up: true,
      icon: 'bi-check-circle', iconBg: '#f0f2fa', iconColor: '#363f7e' },
  ];

  const monthVals = [2.8, 3.4, 3.1, 4.2, 3.9, 4.6, 3.3, 4.8, 5.1, 4.4, 3.7, 4.6];
  const labels = ['Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
  const maxV = Math.max(...monthVals);
  const months = monthVals.map((v, i) => ({ label: labels[i], pct: (v / maxV * 88 + 8).toFixed(1), amount: fmtCr(v, currency) }));
  const spendTotal = fmtCrTotal(monthVals.reduce((a, b) => a + b, 0), currency);

  const C = 2 * Math.PI * 70;
  const segRaw = [
    { label: 'Approved', count: '86', color: '#0bc472', frac: 0.62 },
    { label: 'Pending L2 / L3', count: '29', color: '#ff6f02', frac: 0.21 },
    { label: 'In Review', count: '15', color: '#0878f1', frac: 0.11 },
    { label: 'Rejected', count: '8', color: '#e40032', frac: 0.06 },
  ];
  let acc = 0;
  const donut = segRaw.map(s => {
    const len = s.frac * C;
    const seg = { ...s, dashArray: `${len.toFixed(1)} ${(C - len).toFixed(1)}`, dashOffset: (-acc).toFixed(1), pctLabel: Math.round(s.frac * 100) + '%' };
    acc += len; return seg;
  });

  const moduleCards = [
    { id: 'onboarding', title: 'Vendor Onboarding', status: '11 in progress', color: '#ff6f02',
      icon: '<circle cx="9" cy="8" r="3.2"></circle><path d="M3.5 20c.6-3.4 3-5.2 5.5-5.2S13.9 16.6 14.5 20"></path><path d="M17 8h5M19.5 5.5v5"></path>' },
    { id: 'empanelment', title: 'Vendor Empanelment', status: '6 awaiting approval', color: '#ff6f02',
      icon: '<path d="M12 3l7 3v5c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V6z"></path><path d="M9 12l2 2 4-4.2"></path>' },
    { id: 'requisition', title: 'Purchase Requisition', status: '9 to approve', color: '#ff6f02',
      icon: '<rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 8h6M9 12h6M9 16h4"></path>' },
    { id: 'rfq', title: 'RFQ & Quotations', status: '6 open', color: '#0878f1',
      icon: '<path d="M4 5h16v10H8l-4 4z"></path><path d="M8 9h8M8 12h5"></path>' },
    { id: 'po', title: 'Purchase Orders', status: '31 active', color: '#0bc472',
      icon: '<path d="M6 6h15l-1.6 8H7.5z"></path><circle cx="9" cy="19" r="1.4"></circle><circle cx="18" cy="19" r="1.4"></circle><path d="M6 6L5 3H3"></path>' },
    { id: 'grn', title: 'Goods Receipt', status: '4 to inspect', color: '#7A4FA0',
      icon: '<path d="M3 8l9-4 9 4-9 4z"></path><path d="M3 8v8l9 4 9-4V8"></path><path d="M12 12v8"></path>' },
  ];

  const travelCards = [
    { id: 'trf', title: 'Travel Requisition', status: '7 to approve', color: '#ff6f02',
      icon: '<rect x="4" y="8" width="16" height="12" rx="2"></rect><path d="M9 8V6a2 2 0 012-2h2a2 2 0 012 2v2M12 12v4"></path>' },
    { id: 'flight', title: 'Flight & Boarding', status: '5 to book', color: '#0878f1', linejoin: true,
      icon: '<path d="M4 13l16-8-5 16-3-6z"></path><path d="M12 15l3 5"></path>' },
    { id: 'reimb', title: 'Reimbursement', status: '6 awaiting finance', color: '#ff6f02',
      icon: '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"></path><path d="M9 8h6M9 12h6M9 16h3"></path>' },
    { id: 'traveleval', title: 'Vendor Evaluation', status: '9 active vendors', color: '#0bc472', linejoin: true,
      icon: '<path d="M12 4l2.4 5 5.6.5-4.2 3.7 1.3 5.3L12 16.9 7.9 18.5l1.3-5.3L5 9.5l5.6-.5z"></path>' },
  ];

  const activity = [
    { text: 'CFO approved empanelment of Orion Travel Desk LLP', meta: '15 Jul · 16:52 · Empanelment', dot: '#0bc472' },
    { text: 'RFQ-3310 floated to 5 vendors for laptop procurement', meta: '15 Jul · 14:20 · RFQ', dot: '#0878f1' },
    { text: 'PR-8842 awaiting L2 approval (₹28.0 L)', meta: '14 Jul · 11:08 · Requisition', dot: '#ff6f02' },
    { text: 'GRN-7701 rejected — quality inspection failed', meta: '13 Jul · 17:41 · Goods Receipt', dot: '#e40032' },
    { text: 'Zenith Consulting Group marked as Blacklisted', meta: '12 Jul · 10:15 · Empanelment', dot: '#e40032' },
  ];

  return { kpis: kpiRaw, months, spendTotal, donut, donutTotal: '138', moduleCards, travelCards, activity };
}

const GRIEVANCE_STAGE_META = {
  'Admin Head': ['#FFF0E5', '#ff6f02'],
  'CFO': ['#E6F1FE', '#0878f1'],
  'Secretary General': ['#FDE6E9', '#e40032'],
  'Resolved': ['#E5F9F0', '#0bc472'],
};
const GRIEVANCE_ROWS_RAW = [
  { ref: 'GRV-1042', subject: 'Delayed PO dispatch — Sunrise Facility Services', raisedBy: 'P. Nair (PR Sr. Executive)', module: 'Purchase Order', stage: 'Admin Head', daysOpen: 2 },
  { ref: 'GRV-1039', subject: 'GRN quantity mismatch not resolved by vendor', raisedBy: 'M. Iyer (Store Officer)', module: 'Goods Receipt', stage: 'CFO', daysOpen: 6 },
  { ref: 'GRV-1035', subject: 'TRF advance not released before travel date', raisedBy: 'A. Sharma (Events)', module: 'Travel Requisition', stage: 'Secretary General', daysOpen: 9 },
  { ref: 'GRV-1028', subject: 'Reimbursement claim rejected without reason', raisedBy: 'R. Verma (Buyer)', module: 'Reimbursement', stage: 'Resolved', daysOpen: 0 },
  { ref: 'GRV-1021', subject: 'Vendor empanelment stuck beyond SLA at L2', raisedBy: 'S. Kapoor (Admin Team)', module: 'Vendor Empanelment', stage: 'Admin Head', daysOpen: 1 },
];
function grievanceData() {
  const rows = GRIEVANCE_ROWS_RAW.map(g => {
    const [bg, color] = GRIEVANCE_STAGE_META[g.stage];
    return { ...g, pillBg: bg, pillColor: color };
  });
  const stats = [
    { label: 'With Admin Head', count: String(rows.filter(r => r.stage === 'Admin Head').length), color: '#ff6f02' },
    { label: 'With CFO', count: String(rows.filter(r => r.stage === 'CFO').length), color: '#0878f1' },
    { label: 'With Secretary General', count: String(rows.filter(r => r.stage === 'Secretary General').length), color: '#e40032' },
    { label: 'Resolved', count: String(rows.filter(r => r.stage === 'Resolved').length), color: '#0bc472' },
  ];
  return { rows, stats };
}
