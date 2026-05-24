<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { 
    PhBank, PhReceipt, PhMoney, PhChartLineUp, 
    PhPlus, PhRobot, PhDotsThree, PhFilePdf, 
    PhPaperPlaneRight, PhTrash, PhCheckCircle, 
    PhUsers, PhTrendUp, PhTrendDown, PhPiggyBank, PhHourglass,
    PhPencilSimple, PhCheck, PhX, PhRepeat, PhProhibit, PhXCircle
} from '@phosphor-icons/vue';
import dayjs from 'dayjs';
import { useModalStore } from '@/Stores/useModalStore';
import { useToastStore } from '@/Stores/useToastStore';

const modalStore = useModalStore();
const toastStore = useToastStore();

interface Invoice {
    id: string; invoice_number: string; issue_date: string; due_date: string;
    total: string; balance_due: string; status: string; currency: string;
    client: { id: string; name: string; city: string; country: string; };
}

interface Bill {
    id: string; bill_number: string; vendor_name: string; category: string;
    amount: string; due_date: string; payment_date: string | null; status: string;
    notes: string | null;
}

const metrics = ref({
    total_revenue: 0, outstanding_balance: 0, overdue_amount: 0,
    total_expenses: 0, cash_balance: 0, net_burn_rate: 0,
    runway_months: 0, monthly_payroll: 0, monthly_bills: 0
});
const invoices = ref<Invoice[]>([]);
const bills = ref<Bill[]>([]);
const activeTab = ref('dashboard'); // dashboard, invoices, bills, sales-synergy, hr-synergy

// Synergy states
const wonDeals = ref<any[]>([]);
const payrollData = ref<any>({ employees: [], total_monthly_payroll: 0, payroll_posted_this_month: false, current_month: '' });

const fetchDashboard = async () => {
    try {
        const response = await axios.get('/api/finance/dashboard');
        metrics.value = response.data.metrics;
        invoices.value = response.data.invoices;
        bills.value = response.data.bills;
    } catch (error) {
        console.error('Failed to fetch finance dashboard', error);
    }
};

const fetchWonDeals = async () => {
    try {
        const response = await axios.get('/api/finance/synergy/won-deals');
        wonDeals.value = response.data;
    } catch (e) {
        console.error('Failed to fetch won deals queue', e);
    }
};

const fetchPayroll = async () => {
    try {
        const response = await axios.get('/api/finance/synergy/payroll');
        payrollData.value = response.data;
    } catch (e) {
        console.error('Failed to fetch payroll metrics', e);
    }
};

const handleDashboardRefresh = () => {
    fetchDashboard();
    if (activeTab.value === 'sales-synergy') fetchWonDeals();
    if (activeTab.value === 'hr-synergy') fetchPayroll();
};

const changeTab = (tab: string) => {
    activeTab.value = tab;
    if (tab === 'sales-synergy') fetchWonDeals();
    if (tab === 'hr-synergy') fetchPayroll();
};

const formatCurrency = (amount: number | string) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(amount));
};

const getStatusBadge = (status: string) => {
    const badges: Record<string, string> = {
        'paid': 'bg-green-100 text-green-700 border-green-200',
        'draft': 'bg-slate-100 text-slate-500 border-slate-200',
        'sent': 'bg-blue-100 text-blue-700 border-blue-200',
        'overdue': 'bg-red-100 text-red-700 border-red-200',
        'partially_paid': 'bg-amber-100 text-amber-700 border-amber-200'
    };
    return badges[status] || badges['draft'];
};

const getBillStatusBadge = (status: string) => {
    const badges: Record<string, string> = {
        'paid': 'bg-green-50 text-green-700 border-green-200',
        'unpaid': 'bg-amber-50 text-amber-700 border-amber-200',
        'voided': 'bg-slate-100 text-slate-600 border-slate-200',
        'cancelled': 'bg-red-50 text-red-700 border-red-200'
    };
    return badges[status] || 'bg-amber-50 text-amber-700 border-amber-200';
};

// Actions API Calls
const payVendorBill = async (id: string) => {
    if (!confirm('Are you sure you want to mark this bill as paid?')) return;
    try {
        await axios.put(`/api/finance/bills/${id}/pay`);
        toastStore.showToast('Bill marked as paid and cash outflows updated.', 'success');
        fetchDashboard();
    } catch (e) {
        toastStore.showToast('Failed to pay bill', 'error');
    }
};

const deleteVendorBill = async (id: string) => {
    if (!confirm('Are you sure you want to delete this bill?')) return;
    try {
        await axios.delete(`/api/finance/bills/${id}`);
        toastStore.showToast('Vendor bill deleted successfully.', 'success');
        fetchDashboard();
    } catch (e) {
        toastStore.showToast('Failed to delete bill', 'error');
    }
};

const voidVendorBill = async (id: string) => {
    if (!confirm('Are you sure you want to void this bill? (This is a permanent accounting reversal)')) return;
    try {
        await axios.put(`/api/finance/bills/${id}/void`);
        toastStore.showToast('Vendor bill voided successfully.', 'success');
        fetchDashboard();
    } catch (e) {
        toastStore.showToast('Failed to void bill.', 'error');
    }
};

const cancelVendorBill = async (id: string) => {
    if (!confirm('Are you sure you want to cancel this bill?')) return;
    try {
        await axios.put(`/api/finance/bills/${id}/cancel`);
        toastStore.showToast('Vendor bill cancelled successfully.', 'success');
        fetchDashboard();
    } catch (e) {
        toastStore.showToast('Failed to cancel bill.', 'error');
    }
};

const autoBillDeal = async (deal: any) => {
    try {
        const isRecurring = deal.payment_type === 'recurring';
        const amount = (isRecurring && Number(deal.recurring_amount) > 0)
            ? Number(deal.recurring_amount)
            : (Number(deal.value) || 0);

        const payload = {
            client_id: deal.crm_account_id,
            issue_date: dayjs().format('YYYY-MM-DD'),
            due_date: dayjs().add(15, 'day').format('YYYY-MM-DD'),
            tax_rate: 0,
            deal_id: deal.id,
            items: [
                {
                    description: isRecurring 
                        ? `Recurring Contract Services (${deal.recurring_frequency}): ${deal.title}`
                        : `Contract Services for Deal: ${deal.title}`,
                    quantity: 1,
                    price: amount
                }
            ]
        };
        await axios.post('/api/finance/invoices', payload);
        toastStore.showToast(`Invoice generated successfully for ${deal.title}!`, 'success');
        fetchDashboard();
        fetchWonDeals();
    } catch (error) {
        toastStore.showToast('Failed to auto-bill deal', 'error');
    }
};

const triggerPayrollPosting = async () => {
    try {
        await axios.post('/api/finance/synergy/payroll/post');
        toastStore.showToast('Payroll operating costs successfully posted into Finance Ledger!', 'success');
        fetchDashboard();
        fetchPayroll();
    } catch (e: any) {
        toastStore.showToast(e.response?.data?.message || 'Failed to post payroll overhead', 'error');
    }
};

const exportCSV = () => {
    if (invoices.value.length === 0) {
        alert("No invoices to export.");
        return;
    }
    const headers = ['Invoice ID', 'Client', 'Issue Date', 'Amount', 'Status'];
    const rows = invoices.value.map(i => [
        i.invoice_number,
        `"${i.client?.name || 'Unknown client'}"`,
        i.issue_date,
        i.total,
        i.status
    ]);
    const csvContent = [headers, ...rows].map(e => e.join(",")).join("\n");
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "invoices_export.csv");
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadInvoicePDF = async (invoice: Invoice) => {
    // Fetch business profile from settings
    let biz: any = { name: 'SEHTECH SOLUTIONS', currency: 'USD' };
    try {
        const resp = await axios.get('/api/settings');
        const cp = resp.data?.config?.company_profile;
        if (cp) biz = { ...biz, ...cp };
    } catch (_) {}

    const bizName = biz.name || 'SEHTECH SOLUTIONS';
    const bizEmail = biz.email || 'billing@sehtech.com';
    const bizPhone = biz.phone || '';
    const bizAddress = biz.address || '';
    const bizWebsite = biz.website || 'sehtech.com';
    const bizLogo = biz.logo_url || '';

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert("Please allow popups to download/print statements.");
        return;
    }

    const itemsHtml = ((invoice as any).items || []).map((item: any) => `
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; font-size: 13px; color: #334155;">${item.description}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; text-align: center; font-size: 13px; color: #64748B;">${item.quantity}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; text-align: right; font-size: 13px; color: #64748B;">${formatCurrency(item.price)}</td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; text-align: right; font-size: 13px; font-weight: 600; color: #1E293B;">${formatCurrency(item.quantity * item.price)}</td>
        </tr>
    `).join('');

    const subtotal = Number((invoice as any).subtotal || invoice.total);
    const taxRate = Number((invoice as any).tax_rate) || 0;
    const taxAmount = Number((invoice as any).tax_amount) || 0;
    const total = Number(invoice.total);
    const balanceDue = Number(invoice.balance_due);
    const isPaid = invoice.status === 'paid';

    const logoBlock = bizLogo
        ? `<img src="${bizLogo}" alt="Logo" style="height: 48px; object-fit: contain;" />`
        : `<div style="font-size: 28px; font-weight: 900; letter-spacing: -1px; color: #854D0E; font-family: 'Inter', 'Segoe UI', sans-serif;">${bizName}</div>`;

    printWindow.document.write(`
        <html>
        <head>
            <title>Invoice - ${invoice.invoice_number}</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Inter', 'Segoe UI', 'Helvetica Neue', sans-serif; color: #1E293B; background: #fff; }
                .page { max-width: 800px; margin: 0 auto; padding: 48px 56px; position: relative; }
                .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-35deg); font-size: 120px; font-weight: 900; color: rgba(22,163,106,0.06); pointer-events: none; z-index: 0; letter-spacing: 10px; }
                .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; position: relative; z-index: 1; }
                .header-left { max-width: 55%; }
                .company-tagline { font-size: 11px; color: #94A3B8; margin-top: 6px; letter-spacing: 1px; text-transform: uppercase; }
                .company-details { font-size: 11px; color: #64748B; line-height: 1.7; margin-top: 8px; }
                .invoice-badge { display: inline-block; padding: 6px 20px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; }
                .badge-paid { background: #DCFCE7; color: #166534; }
                .badge-unpaid { background: #FEF3C7; color: #92400E; }
                .invoice-number { font-size: 14px; font-weight: 700; color: #475569; margin-top: 8px; }
                .accent-bar { height: 4px; background: linear-gradient(90deg, #854D0E, #D97706, #F59E0B); border-radius: 2px; margin-bottom: 36px; }
                .parties { display: flex; justify-content: space-between; margin-bottom: 40px; padding: 24px; background: #F8FAFC; border-radius: 12px; border: 1px solid #F1F5F9; }
                .party-label { font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
                .party-name { font-size: 16px; font-weight: 700; color: #0F172A; }
                .party-info { font-size: 12px; color: #64748B; line-height: 1.7; margin-top: 4px; }
                .dates-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 36px; }
                .date-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 10px; padding: 14px 16px; }
                .date-label { font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; }
                .date-value { font-size: 14px; font-weight: 600; color: #1E293B; margin-top: 4px; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 32px; border-radius: 12px; overflow: hidden; border: 1px solid #E2E8F0; }
                .items-table th { background: #0F172A; color: #F8FAFC; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; padding: 14px 16px; text-align: left; font-weight: 700; }
                .items-table th:nth-child(2), .items-table th:nth-child(3), .items-table th:nth-child(4) { text-align: center; }
                .items-table th:last-child { text-align: right; }
                .summary-box { display: flex; justify-content: flex-end; margin-bottom: 40px; }
                .summary-table { width: 300px; border-collapse: collapse; }
                .summary-table td { padding: 10px 14px; font-size: 13px; }
                .summary-table .label { color: #64748B; }
                .summary-table .value { text-align: right; font-weight: 500; color: #1E293B; }
                .total-row td { border-top: 2px solid #0F172A; padding-top: 14px; font-size: 18px !important; font-weight: 800 !important; }
                .total-row .value { color: #854D0E; }
                .balance-row td { background: ${isPaid ? '#DCFCE7' : '#FEF2F2'}; border-radius: 8px; font-weight: 700; font-size: 14px; color: ${isPaid ? '#166534' : '#DC2626'}; }
                .footer { margin-top: 48px; padding-top: 24px; border-top: 1px solid #E2E8F0; text-align: center; }
                .footer-thanks { font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px; }
                .footer-contact { font-size: 11px; color: #94A3B8; line-height: 1.7; }
                .footer-brand { margin-top: 16px; font-size: 10px; color: #CBD5E1; letter-spacing: 2px; text-transform: uppercase; }
                @media print {
                    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .no-print { display: none !important; }
                    .page { padding: 24px 32px; }
                }
            </style>
        </head>
        <body>
            <div class="page">
                <div class="watermark">${isPaid ? 'PAID' : ''}</div>

                <div class="header">
                    <div class="header-left">
                        ${logoBlock}
                        <div class="company-tagline">Enterprise Solutions</div>
                        <div class="company-details">
                            ${bizAddress ? bizAddress + '<br>' : ''}
                            ${bizPhone ? '📞 ' + bizPhone + '<br>' : ''}
                            ${bizEmail ? '✉ ' + bizEmail : ''}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="invoice-badge ${isPaid ? 'badge-paid' : 'badge-unpaid'}">${isPaid ? '✓ Paid' : '⏳ ' + invoice.status}</div>
                        <div class="invoice-number">${invoice.invoice_number}</div>
                    </div>
                </div>

                <div class="accent-bar"></div>

                <div class="parties">
                    <div>
                        <div class="party-label">Billed From</div>
                        <div class="party-name">${bizName}</div>
                        <div class="party-info">${bizWebsite}</div>
                    </div>
                    <div style="text-align: right;">
                        <div class="party-label">Billed To</div>
                        <div class="party-name">${invoice.client?.name || 'Valued Customer'}</div>
                        <div class="party-info">${invoice.client?.city ? invoice.client.city + ', ' + invoice.client.country : ''}</div>
                    </div>
                </div>

                <div class="dates-grid">
                    <div class="date-card">
                        <div class="date-label">Issue Date</div>
                        <div class="date-value">${dayjs(invoice.issue_date).format('MMM D, YYYY')}</div>
                    </div>
                    <div class="date-card">
                        <div class="date-label">Due Date</div>
                        <div class="date-value">${dayjs(invoice.due_date).format('MMM D, YYYY')}</div>
                    </div>
                    <div class="date-card">
                        <div class="date-label">Currency</div>
                        <div class="date-value">${invoice.currency || biz.currency || 'USD'}</div>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="width: 70px; text-align: center;">Qty</th>
                            <th style="width: 120px; text-align: right;">Unit Price</th>
                            <th style="width: 120px; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml || '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #94A3B8;">Service charges as per agreement</td></tr>'}
                    </tbody>
                </table>

                <div class="summary-box">
                    <table class="summary-table">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="value">${formatCurrency(subtotal)}</td>
                        </tr>
                        ${taxRate > 0 ? `
                        <tr>
                            <td class="label">Tax (${taxRate}%)</td>
                            <td class="value">${formatCurrency(taxAmount)}</td>
                        </tr>` : ''}
                        <tr class="total-row">
                            <td class="label">Total</td>
                            <td class="value">${formatCurrency(total)}</td>
                        </tr>
                        <tr class="balance-row">
                            <td style="padding: 12px 14px;">${isPaid ? 'Fully Paid' : 'Balance Due'}</td>
                            <td style="text-align: right; padding: 12px 14px;">${formatCurrency(balanceDue)}</td>
                        </tr>
                    </table>
                </div>

                <div class="footer">
                    <div class="footer-thanks">Thank you for your business!</div>
                    <div class="footer-contact">
                        For questions about this invoice, contact us at ${bizEmail}<br>
                        Payment is due by ${dayjs(invoice.due_date).format('MMMM D, YYYY')}
                    </div>
                    <div class="footer-brand">Powered by ${bizName} · Finance Center</div>
                </div>
            </div>

            <div class="no-print" style="position: fixed; bottom: 20px; right: 20px; z-index: 999;">
                <button onclick="window.print()" style="padding: 12px 28px; background: #854D0E; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    🖨 Print / Save as PDF
                </button>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
};

const downloadBillPDF = async (bill: Bill) => {
    let biz: any = { name: 'SEHTECH SOLUTIONS', currency: 'USD' };
    try {
        const resp = await axios.get('/api/settings');
        const cp = resp.data?.config?.company_profile;
        if (cp) biz = { ...biz, ...cp };
    } catch (_) {}

    const bizName = biz.name || 'SEHTECH SOLUTIONS';
    const bizEmail = biz.email || 'billing@sehtech.com';

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert("Please allow popups to download/print statements.");
        return;
    }

    const isPaid = bill.status === 'paid';
    const amount = Number(bill.amount);

    printWindow.document.write(`
        <html>
        <head>
            <title>Expense Report - ${(bill as any).bill_number || bill.id}</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Inter', 'Segoe UI', sans-serif; color: #1E293B; background: #fff; }
                .page { max-width: 700px; margin: 0 auto; padding: 48px 56px; }
                .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
                .accent-bar { height: 4px; background: linear-gradient(90deg, #DC2626, #F97316, #EAB308); border-radius: 2px; margin-bottom: 36px; }
                .badge { display: inline-block; padding: 6px 16px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; }
                .badge-paid { background: #DCFCE7; color: #166534; }
                .badge-unpaid { background: #FFF9DB; color: #B45309; }
                .badge-voided { background: #F1F5F9; color: #475569; }
                .badge-cancelled { background: #FEE2E2; color: #991B1B; }
                .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 36px; }
                .detail-card { background: #F8FAFC; border-radius: 12px; padding: 20px; border: 1px solid #F1F5F9; }
                .detail-label { font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
                .detail-value { font-size: 15px; font-weight: 600; color: #0F172A; }
                .amount-block { background: #0F172A; border-radius: 16px; padding: 32px; text-align: center; margin-bottom: 36px; }
                .amount-label { font-size: 11px; color: #94A3B8; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; }
                .amount-value { font-size: 36px; font-weight: 900; color: #F59E0B; }
                .notes-section { background: #FFFBEB; border-radius: 12px; padding: 20px; border: 1px solid #FEF3C7; margin-bottom: 36px; }
                .notes-label { font-size: 10px; font-weight: 700; color: #92400E; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
                .notes-text { font-size: 13px; color: #78350F; line-height: 1.6; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #E2E8F0; text-align: center; font-size: 11px; color: #94A3B8; }
                @media print {
                    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="page">
                <div class="header">
                    <div>
                        <div style="font-size: 24px; font-weight: 900; color: #854D0E;">${bizName}</div>
                        <div style="font-size: 11px; color: #94A3B8; margin-top: 4px; letter-spacing: 1px; text-transform: uppercase;">Expense / Vendor Bill Report</div>
                    </div>
                    <div class="badge badge-${bill.status}">${bill.status === 'paid' ? '✓ Settled' : (bill.status === 'unpaid' ? '⏳ Pending' : bill.status?.toUpperCase())}</div>
                </div>

                <div class="accent-bar"></div>

                <div class="amount-block">
                    <div class="amount-label">Total Amount</div>
                    <div class="amount-value">${formatCurrency(amount)}</div>
                </div>

                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-label">Vendor / Payee</div>
                        <div class="detail-value">${bill.vendor_name}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Category</div>
                        <div class="detail-value" style="text-transform: capitalize;">${bill.category?.replace(/_/g, ' ') || 'General'}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Due Date</div>
                        <div class="detail-value">${dayjs(bill.due_date).format('MMMM D, YYYY')}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">${isPaid ? 'Payment Date' : 'Status'}</div>
                        <div class="detail-value">${isPaid && bill.payment_date ? dayjs(bill.payment_date).format('MMMM D, YYYY') : bill.status?.toUpperCase()}</div>
                    </div>
                </div>

                ${bill.notes ? `
                <div class="notes-section">
                    <div class="notes-label">Notes & Description</div>
                    <div class="notes-text">${bill.notes}</div>
                </div>` : ''}

                <div class="footer">
                    <div style="margin-bottom: 8px;">This is a computer-generated expense report from ${bizName} Finance Center.</div>
                    <div>For inquiries, contact ${bizEmail}</div>
                </div>
            </div>

            <div class="no-print" style="position: fixed; bottom: 20px; right: 20px;">
                <button onclick="window.print()" style="padding: 12px 28px; background: #DC2626; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    🖨 Print / Save as PDF
                </button>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
};

const editingEmployeeId = ref<string | null>(null);
const editingSalary = ref<number | string>(0);

const startEditingSalary = (emp: any) => {
    editingEmployeeId.value = emp.user_id;
    editingSalary.value = emp.salary;
};

const saveEmployeeSalary = async (emp: any) => {
    try {
        await axios.put(`/api/hr/employees/${emp.user_id}`, { 
            salary: Number(editingSalary.value),
            job_title: emp.job_title
        });
        toastStore.showToast('Employee salary updated and payroll metrics synced!', 'success');
        editingEmployeeId.value = null;
        fetchDashboard();
        fetchPayroll();
        window.dispatchEvent(new CustomEvent('refresh-hr-dashboard'));
    } catch (e) {
        toastStore.showToast('Failed to update salary', 'error');
    }
};

const triggerAIToast = (msg: string) => {
    alert(`FINANCE NEXUS: ${msg}`);
};

onMounted(() => {
    fetchDashboard();
    window.addEventListener('refresh-finance-dashboard', handleDashboardRefresh);
});

onUnmounted(() => {
    window.removeEventListener('refresh-finance-dashboard', handleDashboardRefresh);
});
</script>

<template>
    <div class="h-full flex flex-row bg-shell-panel text-text-primary overflow-hidden font-sans">
        
        <!-- Sidebar Navigation -->
        <div class="w-[240px] flex-shrink-0 border-r border-shell-border bg-shell-panel flex flex-col h-full">
            <div class="h-[56px] flex items-center px-6 border-b border-shell-border bg-white shrink-0 gap-3">
                <PhBank :size="24" class="text-dept-finance-main" weight="fill" />
                <h2 class="text-[15px] font-bold text-text-primary">Finance</h2>
            </div>
            
            <div class="flex-1 overflow-y-auto p-3 flex flex-col gap-1">
                <button 
                    @click="changeTab('dashboard')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'dashboard' ? 'bg-dept-finance-main/10 text-dept-finance-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhChartLineUp :size="18" :weight="activeTab === 'dashboard' ? 'fill' : 'regular'" />
                    Overview
                </button>
                <button 
                    @click="changeTab('invoices')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'invoices' ? 'bg-dept-finance-main/10 text-dept-finance-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhReceipt :size="18" :weight="activeTab === 'invoices' ? 'fill' : 'regular'" />
                    Invoices (Receivables)
                </button>
                <button 
                    @click="changeTab('bills')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'bills' ? 'bg-dept-finance-main/10 text-dept-finance-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhMoney :size="18" :weight="activeTab === 'bills' ? 'fill' : 'regular'" />
                    Bills (Payables)
                </button>
                
                <div class="text-[10px] font-bold text-text-disabled uppercase px-3 pt-6 pb-2">Synergies</div>
                
                <button 
                    @click="changeTab('sales-synergy')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'sales-synergy' ? 'bg-dept-finance-main/10 text-dept-finance-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhPaperPlaneRight :size="18" :weight="activeTab === 'sales-synergy' ? 'fill' : 'regular'" />
                    Sales Won Billing
                    <span v-if="wonDeals.length" class="ml-auto w-5 h-5 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center font-bold">{{ wonDeals.length }}</span>
                </button>
                <button 
                    @click="changeTab('hr-synergy')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-btn text-[13px] font-medium transition-colors border-0 bg-transparent cursor-pointer text-left"
                    :class="activeTab === 'hr-synergy' ? 'bg-dept-finance-main/10 text-dept-finance-main font-bold' : 'text-text-secondary hover:bg-shell-border/50'"
                >
                    <PhUsers :size="18" :weight="activeTab === 'hr-synergy' ? 'fill' : 'regular'" />
                    HR Payroll Sync
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-full bg-[#F8FAFC] overflow-hidden">
            <div class="h-[56px] bg-white border-b border-shell-border flex items-center px-6 shrink-0 justify-between">
                <h2 class="text-[16px] font-bold text-text-primary capitalize">{{ activeTab.replace('-', ' ') }}</h2>
                <div class="flex gap-2">
                    <button v-if="activeTab === 'invoices'" @click="modalStore.openModal('record-payment')" class="px-4 py-1.5 bg-white border border-shell-border text-text-primary text-[13px] font-medium rounded-btn hover:bg-shell-panel transition-colors cursor-pointer">
                        Record Payment
                    </button>
                    <button v-if="activeTab === 'invoices'" @click="exportCSV" class="px-4 py-1.5 bg-white border border-shell-border text-text-primary text-[13px] font-medium rounded-btn hover:bg-shell-panel transition-colors cursor-pointer">
                        Export CSV
                    </button>
                    <button v-if="activeTab === 'invoices'" @click="modalStore.openModal('new-invoice')" class="flex items-center gap-2 px-4 py-1.5 bg-dept-finance-main text-white text-[13px] font-medium rounded-btn hover:bg-[#A16207] transition-colors shadow-sm border-0 cursor-pointer">
                        <PhPlus :size="16" weight="bold" /> New Invoice
                    </button>
                    <button v-if="activeTab === 'bills'" @click="modalStore.openModal('new-bill')" class="flex items-center gap-2 px-4 py-1.5 bg-dept-finance-main text-white text-[13px] font-medium rounded-btn hover:bg-[#A16207] transition-colors shadow-sm border-0 cursor-pointer">
                        <PhPlus :size="16" weight="bold" /> Record Bill
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto p-6">
                
                <!-- Overview Tab -->
                <div v-if="activeTab === 'dashboard'" class="space-y-6">
                    
                    <!-- Startup Survival Metrics & Gauge -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        
                        <!-- Circular Runway Gauge -->
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col items-center justify-center text-center relative overflow-hidden">
                            <span class="text-[12px] font-bold text-text-disabled uppercase mb-2">Startup Cash Runway</span>
                            
                            <div class="relative w-28 h-28 flex items-center justify-center mb-2">
                                <svg class="w-full h-full transform -rotate-90">
                                    <circle cx="56" cy="56" r="48" stroke="#F1F5F9" stroke-width="8" fill="transparent" />
                                    <circle cx="56" cy="56" r="48" 
                                            :stroke="metrics.runway_months >= 12 ? '#22C55E' : (metrics.runway_months >= 6 ? '#F59E0B' : '#EF4444')" 
                                            stroke-width="8" 
                                            fill="transparent" 
                                            :stroke-dasharray="2 * Math.PI * 48"
                                            :stroke-dashoffset="(2 * Math.PI * 48) * (1 - Math.min(metrics.runway_months, 18) / 18)" 
                                            stroke-linecap="round" />
                                </svg>
                                <div class="absolute flex flex-col items-center justify-center">
                                    <span class="text-[26px] font-black leading-none" :class="metrics.runway_months >= 12 ? 'text-green-600' : (metrics.runway_months >= 6 ? 'text-amber-500' : 'text-red-500')">
                                        {{ metrics.runway_months >= 99 ? '∞' : metrics.runway_months }}
                                    </span>
                                    <span class="text-[9px] font-bold text-text-disabled uppercase mt-0.5">Months</span>
                                </div>
                            </div>
                            
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" 
                                  :class="metrics.runway_months >= 12 ? 'bg-green-50 text-green-700 border-green-200' : (metrics.runway_months >= 6 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200')">
                                {{ metrics.runway_months >= 12 ? 'Healthy Reserve' : (metrics.runway_months >= 6 ? 'Nearing Burn Threshold' : 'Critical Runway') }}
                            </span>
                        </div>

                        <!-- Active Cash Reserves -->
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col justify-between">
                            <div>
                                <span class="text-[12px] font-bold text-text-disabled uppercase flex items-center gap-1.5"><PhPiggyBank :size="14" /> Startup Cash Reserve</span>
                                <div class="text-[28px] font-black text-text-primary mt-2">{{ formatCurrency(metrics.cash_balance) }}</div>
                            </div>
                            <div class="text-[11px] text-text-secondary pt-2 border-t border-shell-border flex justify-between">
                                <span>Initial Seed Reserve:</span>
                                <span class="font-bold">$150,000.00</span>
                            </div>
                        </div>

                        <!-- Net Monthly Burn -->
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col justify-between">
                            <div>
                                <span class="text-[12px] font-bold text-text-disabled uppercase flex items-center gap-1.5"><PhTrendDown :size="14" /> Monthly Net Burn Rate</span>
                                <div class="text-[28px] font-black text-red-600 mt-2">{{ formatCurrency(metrics.net_burn_rate) }}</div>
                            </div>
                            <div class="text-[11px] text-text-secondary pt-2 border-t border-shell-border flex justify-between">
                                <span>Income minus Outgoings:</span>
                                <span class="font-bold text-text-primary">{{ metrics.net_burn_rate > 0 ? 'Deficit' : 'Surplus (Profitable)' }}</span>
                            </div>
                        </div>

                        <!-- Income Yield -->
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col justify-between">
                            <div>
                                <span class="text-[12px] font-bold text-text-disabled uppercase flex items-center gap-1.5"><PhTrendUp :size="14" /> Total Revenue (YTD)</span>
                                <div class="text-[28px] font-black text-green-600 mt-2">{{ formatCurrency(metrics.total_revenue) }}</div>
                            </div>
                            <div class="text-[11px] text-text-secondary pt-2 border-t border-shell-border flex justify-between">
                                <span>Outstanding Invoices:</span>
                                <span class="font-bold text-dept-finance-main">{{ formatCurrency(metrics.outstanding_balance) }}</span>
                            </div>
                        </div>

                    </div>

                    <!-- Ledger Summary & Analytics -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Financial Breakdown Card -->
                        <div class="bg-white border border-shell-border rounded-card shadow-sm p-5">
                            <h3 class="font-bold text-[14px] mb-4 text-text-primary border-b border-shell-border pb-2 flex items-center gap-1.5"><PhChartLineUp /> Operating Expense Breakdown</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center text-[13px]">
                                    <span class="text-text-secondary flex items-center gap-2"><div class="w-3 h-3 rounded bg-red-500"></div> HR Staff Payroll (Monthly)</span>
                                    <span class="font-bold text-text-primary">{{ formatCurrency(metrics.monthly_payroll) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-[13px]">
                                    <span class="text-text-secondary flex items-center gap-2"><div class="w-3 h-3 rounded bg-blue-500"></div> Operating Vendor Bills (Monthly)</span>
                                    <span class="font-bold text-text-primary">{{ formatCurrency(metrics.monthly_bills) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-[13px] pt-2 border-t border-dashed border-shell-border font-bold">
                                    <span>Total Monthly Outflows</span>
                                    <span class="text-red-600">{{ formatCurrency(metrics.monthly_payroll + metrics.monthly_bills) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Overdue Invoices Alert Panel -->
                        <div class="bg-white border border-shell-border rounded-card shadow-sm p-5 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-[14px] mb-3 text-text-primary border-b border-shell-border pb-2 flex items-center gap-1.5"><PhReceipt /> Collection Risks</h3>
                                <div class="flex items-center gap-4 bg-red-50 p-4 rounded-xl border border-red-100">
                                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                                        <PhHourglass :size="20" weight="bold" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-[13px] text-red-800">Risk Mitigation Needed</div>
                                        <p class="text-[12px] text-red-600 mt-0.5">You have <strong class="font-black">{{ formatCurrency(metrics.overdue_amount) }}</strong> outstanding in overdue customer invoices.</p>
                                    </div>
                                </div>
                            </div>
                            <button @click="changeTab('invoices')" class="w-full mt-4 py-2 border border-shell-border hover:border-dept-finance-main hover:text-dept-finance-main bg-white rounded-btn text-[12px] font-bold text-text-secondary transition-colors cursor-pointer">
                                Audit Overdue Invoices
                            </button>
                        </div>

                    </div>

                </div>

                <!-- Invoices Tab -->
                <div v-else-if="activeTab === 'invoices'" class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-shell-panel border-b border-shell-border text-[12px] text-text-disabled uppercase tracking-wider">
                                <th class="px-6 py-3 font-semibold">Invoice ID</th>
                                <th class="px-6 py-3 font-semibold">CRM Account</th>
                                <th class="px-6 py-3 font-semibold">Issue Date</th>
                                <th class="px-6 py-3 font-semibold text-right">Amount</th>
                                <th class="px-6 py-3 font-semibold text-center">Status</th>
                                <th class="px-6 py-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-[13px]">
                            <tr v-if="invoices.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-text-disabled">
                                    <PhReceipt :size="48" weight="thin" class="mx-auto mb-3 text-shell-border" />
                                    No invoices generated yet.
                                </td>
                            </tr>
                            <tr 
                                v-for="invoice in invoices" 
                                :key="invoice.id"
                                class="border-b border-shell-border hover:bg-shell-panel/50 transition-colors"
                            >
                                <td class="px-6 py-4 font-medium text-text-primary">
                                    {{ invoice.invoice_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-text-primary">{{ invoice.client?.name || 'Deleted Account' }}</p>
                                    <p v-if="invoice.client?.city" class="text-[11px] text-text-secondary">{{ invoice.client.city }}, {{ invoice.client.country }}</p>
                                </td>
                                <td class="px-6 py-4 text-text-secondary">
                                    {{ dayjs(invoice.issue_date).format('MMM D, YYYY') }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium">
                                    {{ formatCurrency(invoice.total) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-tag text-[11px] font-bold uppercase tracking-wider border" :class="getStatusBadge(invoice.status)">
                                        {{ invoice.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 text-text-secondary">
                                        <button v-if="invoice.status !== 'paid'" @click="modalStore.openModal('record-payment', { invoiceId: invoice.id })" class="p-1 hover:bg-slate-100 hover:text-dept-finance-main rounded transition-colors text-[11px] font-bold border border-shell-border cursor-pointer bg-white px-2 py-1 shadow-sm" title="Record customer pay">
                                            Pay
                                        </button>
                                        <button @click="downloadInvoicePDF(invoice)" class="p-1.5 hover:bg-slate-100 hover:text-dept-finance-main rounded transition-colors bg-white border border-shell-border cursor-pointer" title="Download PDF Statement">
                                            <PhFilePdf :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Bills (Payables) Tab -->
                <div v-else-if="activeTab === 'bills'" class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden animate-in fade-in duration-200">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-shell-panel border-b border-shell-border text-[12px] text-text-disabled uppercase tracking-wider">
                                <th class="px-6 py-3 font-semibold">Bill ID</th>
                                <th class="px-6 py-3 font-semibold">Vendor / Payee</th>
                                <th class="px-6 py-3 font-semibold">Category</th>
                                <th class="px-6 py-3 font-semibold text-right">Amount</th>
                                <th class="px-6 py-3 font-semibold">Due Date</th>
                                <th class="px-6 py-3 font-semibold text-center">Status</th>
                                <th class="px-6 py-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-[13px]">
                            <tr v-if="bills.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-text-disabled">
                                    <PhMoney :size="48" weight="thin" class="mx-auto mb-3 text-shell-border" />
                                    No outgoing bills recorded yet.
                                </td>
                            </tr>
                            <tr 
                                v-for="bill in bills" 
                                :key="bill.id"
                                class="border-b border-shell-border hover:bg-shell-panel/50 transition-colors"
                            >
                                <td class="px-6 py-4 font-medium text-text-primary">
                                    {{ bill.bill_number }}
                                </td>
                                <td class="px-6 py-4 font-bold text-text-primary">
                                    <div class="flex items-center gap-1.5">
                                        {{ bill.vendor_name }}
                                        <span v-if="bill.is_recurring" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase" :title="`Recurring ${bill.recurring_frequency}`">
                                            <PhRepeat :size="8" /> {{ bill.recurring_frequency }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 capitalize text-text-secondary">
                                    {{ bill.category }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-red-600">
                                    {{ formatCurrency(bill.amount) }}
                                </td>
                                <td class="px-6 py-4 text-text-secondary">
                                    {{ dayjs(bill.due_date).format('MMM D, YYYY') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-tag text-[11px] font-bold uppercase tracking-wider border" :class="getBillStatusBadge(bill.status)">
                                        {{ bill.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 text-text-secondary">
                                        <!-- Mark Paid (only for unpaid) -->
                                        <button v-if="bill.status === 'unpaid'" @click="payVendorBill(bill.id)" class="hover:bg-green-100 hover:text-green-800 rounded transition-colors text-[11px] font-bold border border-green-200 cursor-pointer bg-green-50 text-green-700 px-2 py-1 flex items-center gap-1" title="Mark Paid">
                                            <PhCheckCircle :size="13" /> Mark Paid
                                        </button>
                                        
                                        <!-- Edit Bill (for active bills) -->
                                        <button v-if="bill.status !== 'voided' && bill.status !== 'cancelled'" @click="modalStore.openModal('edit-bill', { bill })" class="p-1.5 hover:bg-slate-100 hover:text-dept-finance-main rounded transition-colors cursor-pointer border border-shell-border bg-white" title="Edit Bill">
                                            <PhPencilSimple :size="15" />
                                        </button>

                                        <!-- Void Bill (for non-voided/cancelled bills) -->
                                        <button v-if="bill.status !== 'voided' && bill.status !== 'cancelled'" @click="voidVendorBill(bill.id)" class="p-1.5 hover:bg-slate-100 hover:text-slate-700 rounded transition-colors cursor-pointer border border-shell-border bg-white" title="Void Bill">
                                            <PhProhibit :size="15" />
                                        </button>

                                        <!-- Cancel Bill (only for unpaid) -->
                                        <button v-if="bill.status === 'unpaid'" @click="cancelVendorBill(bill.id)" class="p-1.5 hover:bg-red-50 hover:text-red-600 rounded transition-colors cursor-pointer border border-shell-border bg-white" title="Cancel Bill">
                                            <PhXCircle :size="15" />
                                        </button>

                                        <!-- Download PDF -->
                                        <button @click="downloadBillPDF(bill)" class="p-1.5 hover:bg-slate-100 hover:text-dept-finance-main rounded transition-colors cursor-pointer border border-shell-border bg-white" title="Download PDF">
                                            <PhFilePdf :size="15" />
                                        </button>

                                        <!-- Delete -->
                                        <button @click="deleteVendorBill(bill.id)" class="p-1.5 hover:bg-red-50 hover:text-red-600 rounded transition-colors cursor-pointer border border-shell-border bg-white" title="Delete Bill">
                                            <PhTrash :size="15" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sales Synergy Tab -->
                <div v-else-if="activeTab === 'sales-synergy'" class="space-y-6 animate-in fade-in duration-200">
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl flex gap-3 text-[13px] text-blue-800">
                        <PhCheckCircle :size="20" class="shrink-0 text-blue-600" />
                        <div>
                            <div class="font-bold">Deal-to-Invoice Automation Queue</div>
                            <p class="mt-0.5">This panel lists all CRM opportunities marked as <strong class="font-extrabold">Closed Won</strong> by the Sales & BD department that have not yet been invoiced. Auto-billing will generate draft invoices with pre-filled line items in a single click.</p>
                        </div>
                    </div>
                    
                    <div class="bg-white border border-shell-border rounded-card shadow-sm overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-shell-panel border-b border-shell-border text-[12px] text-text-disabled uppercase tracking-wider">
                                    <th class="px-6 py-3 font-semibold">Deal Title</th>
                                    <th class="px-6 py-3 font-semibold">CRM Account Name</th>
                                    <th class="px-6 py-3 font-semibold text-right">Value</th>
                                    <th class="px-6 py-3 font-semibold">Stage</th>
                                    <th class="px-6 py-3 font-semibold text-right">Synergy Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-[13px]">
                                <tr v-if="wonDeals.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-text-disabled">
                                        <PhCheckCircle :size="48" weight="thin" class="mx-auto mb-3 text-slate-300" />
                                        No new won deals awaiting invoicing in queue.
                                    </td>
                                </tr>
                                <tr 
                                    v-for="deal in wonDeals" 
                                    :key="deal.id"
                                    class="border-b border-shell-border hover:bg-shell-panel/50 transition-colors"
                                >
                                    <td class="px-6 py-4 font-bold text-text-primary">
                                        {{ deal.title }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ deal.account?.name || 'Unknown client' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-extrabold text-green-600">
                                        {{ formatCurrency(deal.value) }}
                                    </td>
                                    <td class="px-6 py-4 text-text-secondary capitalize">
                                        {{ deal.stage.replace('_', ' ') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="autoBillDeal(deal)" class="px-3 py-1 bg-dept-finance-main hover:bg-[#A16207] text-white text-[12px] font-bold rounded-btn transition-colors border-0 cursor-pointer shadow-sm flex items-center gap-1.5 ml-auto">
                                            <PhReceipt :size="14" /> Auto-Bill Deal
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- HR Synergy Tab -->
                <div v-else-if="activeTab === 'hr-synergy'" class="space-y-6 animate-in fade-in duration-200">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Total Active Staff -->
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm">
                            <span class="text-[12px] font-bold text-text-disabled uppercase">Active Staff Size</span>
                            <div class="text-[28px] font-black text-text-primary mt-2 flex items-center gap-2">
                                <PhUsers :size="24" class="text-dept-finance-main" /> {{ payrollData.employees?.length || 0 }} Employees
                            </div>
                        </div>

                        <!-- Monthly Payroll Burn -->
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm">
                            <span class="text-[12px] font-bold text-text-disabled uppercase">Monthly Payroll Overhead</span>
                            <div class="text-[28px] font-black text-red-600 mt-2">
                                {{ formatCurrency(payrollData.total_monthly_payroll) }}
                            </div>
                        </div>

                        <!-- Lock Payroll posting -->
                        <div class="bg-white p-5 rounded-card border border-shell-border shadow-sm flex flex-col justify-center">
                            <button 
                                @click="triggerPayrollPosting" 
                                :disabled="payrollData.payroll_posted_this_month"
                                class="w-full py-2.5 px-4 font-bold text-[13px] rounded-btn border-0 cursor-pointer shadow-md transition-colors flex items-center justify-center gap-2"
                                :class="payrollData.payroll_posted_this_month 
                                    ? 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-50 cursor-not-allowed opacity-80' 
                                    : 'bg-dept-finance-main hover:bg-[#A16207] text-white'"
                            >
                                <PhCheckCircle v-if="payrollData.payroll_posted_this_month" weight="fill" :size="18" />
                                <PhUsers v-else weight="fill" :size="18" />
                                {{ payrollData.payroll_posted_this_month ? `Posted for ${payrollData.current_month}` : `Post ${payrollData.current_month} Payroll` }}
                            </button>
                        </div>
                    </div>

                    <!-- Staff Salaries Listing -->
                    <div class="bg-white border border-shell-border rounded-card shadow-sm">
                        <h3 class="font-bold text-[14px] p-5 border-b border-shell-border text-text-primary">Staff Salaries Directory (Synced from HR)</h3>
                        <div class="divide-y divide-shell-border max-h-[350px] overflow-y-auto">
                            <div v-if="!payrollData.employees?.length" class="p-8 text-center text-text-disabled">No staff files found in HR database.</div>
                            <div v-for="emp in payrollData.employees" :key="emp.id" class="p-4 flex items-center justify-between text-[13px] hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-dept-finance-main/15 text-dept-finance-main flex items-center justify-center font-bold">
                                        {{ emp.user?.name?.charAt(0) || 'E' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-text-primary">{{ emp.user?.name }}</div>
                                        <div class="text-[11px] text-text-secondary mt-0.5">{{ emp.job_title }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- Display Mode -->
                                    <template v-if="editingEmployeeId !== emp.user_id">
                                        <div class="font-bold text-text-primary text-right">
                                            {{ formatCurrency(emp.salary) }} <span class="text-[10px] font-bold text-text-disabled uppercase">/yr</span>
                                        </div>
                                        <button @click="startEditingSalary(emp)" class="p-1 hover:bg-slate-100 text-text-secondary hover:text-dept-finance-main rounded transition-colors bg-white border border-shell-border cursor-pointer flex items-center justify-center" title="Edit Salary">
                                            <PhPencilSimple :size="13" />
                                        </button>
                                    </template>
                                    
                                    <!-- Edit Mode -->
                                    <template v-else>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[12px] font-bold text-text-secondary">$</span>
                                            <input v-model="editingSalary" type="number" class="w-24 h-8 px-2 border border-shell-border rounded text-[13px] focus:outline-none focus:border-dept-finance-main" />
                                            <button @click="saveEmployeeSalary(emp)" class="p-1 hover:bg-green-50 text-green-700 rounded border border-green-200 bg-green-50 cursor-pointer flex items-center justify-center animate-in fade-in" title="Save Salary">
                                                <PhCheck :size="14" weight="bold" />
                                            </button>
                                            <button @click="editingEmployeeId = null" class="p-1 hover:bg-red-50 text-red-700 rounded border border-red-200 bg-red-50 cursor-pointer flex items-center justify-center animate-in fade-in" title="Cancel">
                                                <PhX :size="14" weight="bold" />
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Right AI Sidebar: Nexus -->
        <div class="w-[300px] flex-shrink-0 border-l border-shell-border bg-white flex flex-col h-full z-10">
            <div class="h-[56px] flex items-center px-4 border-b border-shell-border bg-dept-finance-main text-white shrink-0 gap-2">
                <PhRobot :size="20" weight="fill" />
                <div class="flex flex-col">
                    <span class="text-[14px] font-bold tracking-wide leading-tight">FINANCE NEXUS</span>
                    <span class="text-[10px] text-white/70 uppercase font-semibold">AI Assistant</span>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-4 text-[13px] bg-[#F8FAFC]">
                
                <div class="p-4 bg-white border border-shell-border rounded-card shadow-sm">
                    <p class="text-text-primary leading-relaxed font-medium">
                        Good morning. Your current cash balance is <strong>{{ formatCurrency(metrics.cash_balance) }}</strong>. 
                        You have <strong class="text-red-500">{{ metrics.outstanding_balance > 0 ? 'outstanding receivables' : 'no collection risks' }}</strong>.
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <button @click="triggerAIToast('Drafting emails for collection accounts...')" class="w-full px-4 py-2.5 bg-white border border-shell-border hover:border-dept-finance-main hover:text-dept-finance-main text-text-primary font-medium rounded-btn transition-colors text-left flex items-center justify-between shadow-sm cursor-pointer">
                        Draft Overdue Reminders <PhPaperPlaneRight :size="14" />
                    </button>
                    <button @click="triggerAIToast('Generating automated cash forecast report...')" class="w-full px-4 py-2.5 bg-white border border-shell-border hover:border-dept-finance-main hover:text-dept-finance-main text-text-primary font-medium rounded-btn transition-colors text-left flex items-center justify-between shadow-sm cursor-pointer">
                        Generate Cashflow Forecast <PhChartLineUp :size="14" />
                    </button>
                </div>
            </div>

            <div class="p-4 bg-white border-t border-shell-border shrink-0">
                <div class="relative">
                    <input 
                        type="text" 
                        placeholder="Ask Nexus to generate invoice..." 
                        class="w-full pl-3 pr-10 py-2.5 bg-[#F8FAFC] border border-shell-border rounded-input text-[13px] focus:ring-1 focus:ring-dept-finance-main focus:border-dept-finance-main outline-none transition-all"
                    />
                    <button class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-text-disabled hover:text-dept-finance-main transition-colors bg-transparent border-0 cursor-pointer">
                        <PhRobot :size="18" weight="fill" />
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
