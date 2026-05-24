import { createI18n } from 'vue-i18n';

const messages = {
    en: {
        nav: {
            dashboard: "Dashboard",
            tickets: "My Tickets",
            invoices: "My Invoices",
            documents: "My Documents",
            profile: "My Profile",
            logout: "Sign Out",
        },
        dashboard: {
            welcome: "Welcome back",
            active_tickets: "Active Tickets",
            unpaid_invoices: "Unpaid Invoices",
            recent_documents: "Recent Documents",
            last_login: "Last login:",
            view_all: "View All",
        },
        tickets: {
            title: "Support Tickets",
            new_ticket: "Open New Ticket",
            ticket_title: "Ticket Title",
            description: "Issue Description",
            product: "Related Product",
            submit: "Submit Ticket",
            status: "Status",
            id: "Ticket ID",
            last_updated: "Last Updated",
            no_tickets: "You have no active support tickets.",
            placeholders: {
                title: "Briefly describe the issue...",
                description: "Provide as much detail as possible...",
                product: "Select a product..."
            }
        }
    },
    ar: {
        nav: {
            dashboard: "لوحة التحكم",
            tickets: "تذاكري",
            invoices: "فواتيري",
            documents: "مستنداتي",
            profile: "ملفي الشخصي",
            logout: "تسجيل الخروج",
        },
        dashboard: {
            welcome: "مرحباً بعودتك",
            active_tickets: "التذاكر النشطة",
            unpaid_invoices: "الفواتير غير المدفوعة",
            recent_documents: "المستندات الأخيرة",
            last_login: "آخر تسجيل دخول:",
            view_all: "عرض الكل",
        },
        tickets: {
            title: "تذاكر الدعم",
            new_ticket: "فتح تذكرة جديدة",
            ticket_title: "عنوان التذكرة",
            description: "وصف المشكلة",
            product: "المنتج المتعلق",
            submit: "إرسال التذكرة",
            status: "الحالة",
            id: "رقم التذكرة",
            last_updated: "آخر تحديث",
            no_tickets: "ليس لديك تذاكر دعم نشطة.",
            placeholders: {
                title: "صف المشكلة باختصار...",
                description: "قدم أكبر قدر ممكن من التفاصيل...",
                product: "اختر منتجاً..."
            }
        }
    },
    so: {
        nav: {
            dashboard: "Tusmada",
            tickets: "Tigidhadayda",
            invoices: "Qaansheegyadayda",
            documents: "Dukumiintiyadayda",
            profile: "Koonteeyda",
            logout: "Ka Bax",
        },
        dashboard: {
            welcome: "Soo dhawoow",
            active_tickets: "Tigidhada Furan",
            unpaid_invoices: "Qaansheegyada Aan La Bixin",
            recent_documents: "Dukumiintiyada Cusub",
            last_login: "Gelitaankii ugu dambeeyay:",
            view_all: "Arag Dhammaan",
        },
        tickets: {
            title: "Tigidhada Taageerada",
            new_ticket: "Fur Tigidh Cusub",
            ticket_title: "Ciwaanka Tigidhka",
            description: "Faahfaahinta Dhibaatada",
            product: "Alaabta La Xiriirta",
            submit: "Gudbi Tigidhka",
            status: "Xaaladda",
            id: "Aqoonsiga Tigidhka",
            last_updated: "Cusbooneysiinkii Ugu Dambeeyay",
            no_tickets: "Ma haysatid tigidho taageero oo furan.",
            placeholders: {
                title: "Si kooban u sharax dhibaatada...",
                description: "Bixi faahfaahin dheeraad ah...",
                product: "Dooro alaabta..."
            }
        }
    }
};

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages,
});
