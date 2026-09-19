<?php

/*
|--------------------------------------------------------------------------
| SEO / public marketing site content
|--------------------------------------------------------------------------
|
| Single source of truth for the public pages (titles, meta descriptions,
| headings, body copy, FAQs) and for the XML sitemap. The views in
| resources/views/marketing/ only render what is described here, and
| App\Support\Seo turns it into canonical URLs and JSON-LD.
|
| Keep every title and description unique per page, one H1 per page, and
| only describe features FlatCare really has.
|
*/

return [

    // Canonical origin. Always https + non-www, never a request-derived host,
    // so a preview/staging host or ?utm= query never becomes the canonical.
    'url' => rtrim((string) env('SEO_SITE_URL', 'https://flatcare.in'), '/'),

    'brand' => 'FlatCare',
    'brand_alternates' => ['Flat Care', 'FlatCare Society Management', 'FlatCare App'],
    'tagline' => 'Smart Apartment & Society Management Software',

    'og_image' => '/images/marketing/flatcare-society-management-app.jpg',
    'og_image_width' => 1600,
    'og_image_height' => 666,
    'logo' => '/favicon-192.png',

    'contact' => [
        'email' => 'support@flatcare.in',
        'phones' => ['+91 96646 53896', '+91 97124 23633'],
        'whatsapp' => ['919664653896', '919712423633'],
    ],

    // Google Search Console "HTML tag" verification token (optional). Set
    // GOOGLE_SITE_VERIFICATION in .env to the content= value Search Console gives you.
    'google_verification' => env('GOOGLE_SITE_VERIFICATION'),

    // Date the content of the sitemap entries was last reviewed (YYYY-MM-DD).
    'lastmod' => '2026-09-19',

    'pages' => [

        // ------------------------------------------------------------ HOME
        'home' => [
            'path' => '/',
            'name' => 'Home',
            'title' => 'FlatCare – Smart Apartment & Society Management Software',
            'description' => 'FlatCare is smart apartment and society management software for maintenance billing, expense tracking, accounting, residents and daily society operations.',
            'priority' => '1.0',
            'faqs' => [
                ['What is FlatCare?', 'FlatCare is apartment and society management software for housing societies, apartment buildings and residential communities in India. It brings maintenance billing, fee collection, resident records, complaints, visitor and gate management, announcements and committee tools into one web dashboard and a mobile app.'],
                ['What is apartment management software?', 'Apartment management software is a system that helps a building committee or manager run daily operations digitally: keeping flat and resident details, raising maintenance bills, collecting payments, recording visitors, handling complaints and sharing notices, instead of using registers, spreadsheets and WhatsApp groups.'],
                ['What is society management software?', 'Society management software does the same job for a housing society or RWA. It supports several blocks and many flats, gives the committee and admins the right level of access, and gives residents one place to see bills, notices and society activity.'],
                ['How does FlatCare manage society maintenance?', 'Admins set the fixed maintenance charge, add water readings and any extra charges, and FlatCare prepares a bill for every flat. Residents see their bill in the app, pay online or the office records the payment, and the society can see at any time who has paid and what is still pending.'],
                ['Can FlatCare manage apartment maintenance billing?', 'Yes. Maintenance billing is a core part of FlatCare: fixed monthly maintenance, water charges and one-off charges can be billed per flat, with receipts and an outstanding-dues view for the admin team.'],
                ['Can FlatCare manage society expenses?', 'FlatCare covers the charges side of society finances today: maintenance, water and extra charges, plus payment records and receipts. If you need a specific expense workflow, contact us and we will tell you honestly whether it fits your society.'],
                ['Can residents use FlatCare?', 'Yes. Residents use the FlatCare mobile app to see and pay bills, raise complaints, pre-approve visitors, read announcements and events, vote in polls and find important contacts. Society staff use the web dashboard.'],
                ['Does FlatCare provide society accounting?', 'FlatCare keeps a clear record of bills, payments and receipts for every flat, which is the base of society accounting. It is not a replacement for a chartered accountant’s books, but it removes the manual work of tracking who owes what.'],
                ['Is FlatCare suitable for housing societies?', 'Yes. FlatCare is built for housing societies, apartment associations and RWAs of different sizes, with support for multiple blocks, committee roles, gate security and resident communication.'],
            ],
        ],

        // -------------------------------------------- APARTMENT MANAGEMENT
        'apartment-management-software' => [
            'path' => '/apartment-management-software',
            'name' => 'Apartment Management Software',
            'anchor' => 'apartment management software',
            'title' => 'Apartment Management Software for Buildings | FlatCare',
            'description' => 'FlatCare is apartment management software that helps committees run maintenance billing, resident records, visitors and complaints from one dashboard.',
            'h1' => 'Apartment Management Software for Everyday Building Operations',
            'lead' => 'Running an apartment building means juggling flat records, monthly dues, visitors at the gate, complaints and notices. FlatCare is apartment management software that keeps all of it in one place, so the committee spends less time chasing information and more time improving the building.',
            'sections' => [
                [
                    'h2' => 'What apartment management software should do',
                    'intro' => 'A good system replaces registers and scattered chat groups with one reliable record that everyone can trust.',
                    'items' => [
                        ['h3' => 'Flat and resident records', 'text' => 'Organise blocks, flats, owners and tenants once, and keep contact details and family members up to date without hunting through old sheets.'],
                        ['h3' => 'Maintenance billing and collection', 'text' => 'Generate a bill for every flat, record or collect payments online, and see outstanding dues instantly. Read more about our approach to <a href="/society-maintenance-billing">society maintenance billing</a>.'],
                        ['h3' => 'Visitor and gate management', 'text' => 'Log every entry and exit, let residents pre-approve guests, and give security staff a simple check-in screen.'],
                        ['h3' => 'Complaints and notices', 'text' => 'Residents raise complaints from their phone, admins follow them up, and announcements reach the whole building at once.'],
                    ],
                ],
                [
                    'h2' => 'Built for committees, managers and residents',
                    'intro' => 'FlatCare gives each person the access they need and nothing more.',
                    'items' => [
                        ['h3' => 'Admins and committee members', 'text' => 'Role-based access keeps sensitive tools, such as billing and user management, with the people responsible for them.'],
                        ['h3' => 'Residents', 'text' => 'A mobile app shows bills, notices and visitor approvals, so residents do not have to call the office for routine questions. See the <a href="/apartment-management-app">apartment management app</a>.'],
                        ['h3' => 'Security guards', 'text' => 'Guards get their own simple flow to record visitors and ask a resident for approval before letting someone in.'],
                    ],
                ],
                [
                    'h2' => 'Why buildings move away from spreadsheets',
                    'intro' => 'Spreadsheets and paper registers work until the building grows. Then dues get missed, visitor records go missing and every question turns into a phone call.',
                    'items' => [
                        ['h3' => 'One shared source of truth', 'text' => 'Everyone works from the same up-to-date data, so there is no confusion about who paid or who visited.'],
                        ['h3' => 'Less follow-up work', 'text' => 'Notifications and in-app notices replace repeated calls and messages.'],
                        ['h3' => 'A record you can look back on', 'text' => 'Bills, payments, visitors and complaints stay searchable for the next committee too.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What does apartment management software do?', 'It helps an apartment committee or manager handle flat and resident records, maintenance billing, payment tracking, visitor entries, complaints and announcements from one system instead of separate registers and chats.'],
                ['Is FlatCare suitable for a small apartment building?', 'Yes. FlatCare works for a single building as well as for a large society with several blocks, so you can start small and grow without changing systems.'],
                ['Do residents need to install an app?', 'Residents can use the FlatCare mobile app to see bills, raise complaints and approve visitors, but the committee can manage the building from the web dashboard even if some residents prefer not to install it.'],
                ['Can we try FlatCare before deciding?', 'Yes. You can request a free trial from the website and our team will help set up your building.'],
            ],
            'related' => ['society-management-software', 'society-maintenance-billing', 'apartment-management-app', 'features'],
        ],

        // ------------------------------------------------ SOCIETY MANAGEMENT
        'society-management-software' => [
            'path' => '/society-management-software',
            'name' => 'Society Management Software',
            'anchor' => 'society management software',
            'title' => 'Society Management Software for Housing Societies | FlatCare',
            'description' => 'Manage your housing society with FlatCare: maintenance billing, residents, gate security, complaints, notices, polls and committee tools in one system.',
            'h1' => 'Society Management Software for Housing Societies and RWAs',
            'lead' => 'A housing society has many moving parts: dues from every flat, a busy gate, committee decisions and residents who want quick answers. FlatCare is society management software that connects these pieces, so the managing committee can work from one system and residents always know where to look.',
            'sections' => [
                [
                    'h2' => 'Everything a housing society needs in one place',
                    'intro' => 'FlatCare covers the daily work of a society office without forcing you to use a dozen separate tools.',
                    'items' => [
                        ['h3' => 'Maintenance and other charges', 'text' => 'Fixed maintenance, water charges and extra charges are billed per flat with a clear payment status. See how <a href="/society-maintenance-software">society maintenance software</a> fits into daily work.'],
                        ['h3' => 'Resident and member directory', 'text' => 'Keep member details in order across blocks and flats, with privacy-friendly masking of contact details for other residents.'],
                        ['h3' => 'Security and visitors', 'text' => 'Record who entered and left, let residents approve entries, and keep a gate register the committee can rely on.'],
                        ['h3' => 'Notices, events and polls', 'text' => 'Share announcements, plan events and run polls or elections so decisions are transparent and everyone can take part.'],
                        ['h3' => 'Documents and contacts', 'text' => 'Store society documents and emergency contacts where residents can find them, instead of searching old chats.'],
                    ],
                ],
                [
                    'h2' => 'Made for how societies actually work',
                    'intro' => 'Societies have committees, treasurers, secretaries and guards, each with different responsibilities.',
                    'items' => [
                        ['h3' => 'Committee and admin roles', 'text' => 'Give people the access their role needs. Administrators manage users and billing; committee members handle day-to-day modules.'],
                        ['h3' => 'Multiple blocks and flats', 'text' => 'Model your society the way it is built, with blocks, floors and flats, and manage each in the same system.'],
                        ['h3' => 'A society-wide view', 'text' => 'See collections, complaints and visitor activity across the whole society without collecting reports by hand.'],
                    ],
                ],
                [
                    'h2' => 'Better communication with residents',
                    'intro' => 'Most society disputes start with missing information. FlatCare makes communication visible and traceable.',
                    'items' => [
                        ['h3' => 'One channel for notices', 'text' => 'Announcements are posted once and reach every resident in the app.'],
                        ['h3' => 'Complaints with follow-up', 'text' => 'Every complaint has a status and history, so residents know it was seen and the committee can prove what was done.'],
                        ['h3' => 'A mobile app for residents', 'text' => 'Use the <a href="/society-management-app">society management app</a> for bills, notices and visitor approvals.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is society management software?', 'It is software that helps a housing society or RWA manage maintenance billing, member records, gate security, complaints, notices and committee work digitally from one place.'],
                ['Who uses FlatCare in a housing society?', 'Society admins and committee members use the web dashboard, security guards use a gate screen, and residents use the mobile app. Each role sees only what it needs.'],
                ['Does FlatCare support societies with multiple blocks?', 'Yes. You can organise blocks and flats and manage all of them in one society account.'],
                ['Is resident data private?', 'Access is role based, and the resident directory hides other residents’ full phone numbers and email addresses. Residents always see their own details.'],
                ['How do we get started with FlatCare?', 'Request a free trial on the website. Our team will contact you and help set up your society, blocks, flats and admins.'],
            ],
            'related' => ['apartment-management-software', 'society-accounting-software', 'society-management-app', 'pricing'],
        ],

        // ---------------------------------------------- SOCIETY MAINTENANCE
        'society-maintenance-software' => [
            'path' => '/society-maintenance-software',
            'name' => 'Society Maintenance Software',
            'anchor' => 'society maintenance software',
            'title' => 'Society Maintenance Software & App | FlatCare',
            'description' => 'Track society maintenance dues, water charges, complaints and residents’ payments with FlatCare, simple society maintenance software for committees.',
            'h1' => 'Society Maintenance Software That Keeps Dues and Complaints Organised',
            'lead' => 'Society maintenance is more than collecting a monthly amount. Committees must bill every flat correctly, follow up on dues and respond when residents report a problem. FlatCare society maintenance software gives your team a clear workflow for all of it.',
            'sections' => [
                [
                    'h2' => 'Track maintenance charges flat by flat',
                    'intro' => 'Instead of a large spreadsheet, each flat has its own bills and payment history.',
                    'items' => [
                        ['h3' => 'Fixed maintenance and water charges', 'text' => 'Set the monthly maintenance amount and add water meter readings so consumption-based charges are calculated for you.'],
                        ['h3' => 'Extra charges when needed', 'text' => 'Bill one-off costs such as repairs or festival contributions using reusable fee types.'],
                        ['h3' => 'Clear payment status', 'text' => 'See which bills are paid, partly paid or overdue at a glance. The <a href="/society-maintenance-billing">maintenance billing</a> page explains the flow in detail.'],
                    ],
                ],
                [
                    'h2' => 'Handle complaints and society issues',
                    'intro' => 'Maintenance problems become smaller when they are reported and tracked properly.',
                    'items' => [
                        ['h3' => 'Complaints from the app', 'text' => 'Residents describe an issue from their phone; the committee sees it with its priority and category.'],
                        ['h3' => 'Status and history', 'text' => 'Each complaint moves through clear statuses, so nothing is forgotten and residents can see progress.'],
                        ['h3' => 'Service providers and contacts', 'text' => 'Keep plumbers, electricians and other regular service providers, plus emergency numbers, easy to find.'],
                    ],
                ],
                [
                    'h2' => 'A maintenance app for residents too',
                    'intro' => 'Residents get the same clarity through the <a href="/society-management-app">FlatCare mobile app</a>.',
                    'items' => [
                        ['h3' => 'See dues and receipts', 'text' => 'Residents open the app to see what is due and download proof of what they paid.'],
                        ['h3' => 'Pay online', 'text' => 'Online payment through a secure payment gateway means the office does not need to handle cash for every flat.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is society maintenance software?', 'It is a tool that helps a housing society bill maintenance charges, record payments, follow up on dues and manage resident complaints in an organised way.'],
                ['Can FlatCare handle water charges as part of maintenance?', 'Yes. Water readings can be entered and used to add consumption-based charges to the flat’s bill.'],
                ['Is there a society maintenance app for residents?', 'Yes. The FlatCare mobile app for Android lets residents see bills, pay online, raise complaints and read notices.'],
                ['Can the committee see who has not paid?', 'Yes. The payment status of every bill is visible so the committee can follow up on pending dues.'],
            ],
            'related' => ['society-maintenance-billing', 'apartment-maintenance-management', 'society-management-app', 'features'],
        ],

        // --------------------------------------------- APARTMENT MAINTENANCE
        'apartment-maintenance-management' => [
            'path' => '/apartment-maintenance-management',
            'name' => 'Apartment Maintenance Management',
            'anchor' => 'apartment maintenance management',
            'title' => 'Apartment Maintenance Management Software | FlatCare',
            'description' => 'Manage apartment maintenance charges, billing, payments and resident issues with FlatCare, apartment maintenance management software for associations.',
            'h1' => 'Apartment Maintenance Management Made Simple',
            'lead' => 'For an apartment association, maintenance management means predictable billing, timely collection and a clear response when something needs attention. FlatCare gives apartment committees a straightforward way to run all three without paperwork.',
            'sections' => [
                [
                    'h2' => 'Predictable monthly billing',
                    'intro' => 'When every flat is billed on time and in the same way, disputes drop.',
                    'items' => [
                        ['h3' => 'Bills for every flat', 'text' => 'Bills are prepared for each flat with the agreed maintenance amount and any extra charges, so nothing depends on memory.'],
                        ['h3' => 'Consistent records', 'text' => 'Every bill, payment and receipt is stored against the flat, which makes audits and handovers easier.'],
                    ],
                ],
                [
                    'h2' => 'Follow up without the awkward calls',
                    'intro' => 'Residents stay informed and committees stay in control.',
                    'items' => [
                        ['h3' => 'Outstanding dues at a glance', 'text' => 'Filter unpaid and overdue bills to see exactly who needs a reminder.'],
                        ['h3' => 'Notifications to residents', 'text' => 'Residents receive updates in the app, so important information does not depend on a single WhatsApp group.'],
                        ['h3' => 'Receipts on record', 'text' => 'Payment receipts are available for the resident and the office, which avoids “I already paid” arguments.'],
                    ],
                ],
                [
                    'h2' => 'Part of complete apartment management',
                    'intro' => 'Maintenance works best when it is connected to the rest of the building’s operations.',
                    'items' => [
                        ['h3' => 'Residents and flats', 'text' => 'Bills link to the right flat and resident automatically. Explore <a href="/apartment-management-software">apartment management software</a> features beyond billing.'],
                        ['h3' => 'Complaints and visitors', 'text' => 'Issues raised by residents and visitor logs sit alongside billing in the same dashboard.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is apartment maintenance management?', 'It is the process of billing and collecting maintenance charges from flat owners, recording payments, and handling resident issues in an organised way.'],
                ['Can FlatCare send bills to residents?', 'Bills appear in each resident’s FlatCare app, along with notifications and payment options.'],
                ['Does FlatCare keep payment receipts?', 'Yes. Receipts are generated for recorded payments so both the resident and the office have proof.'],
                ['Can we start using FlatCare mid-year?', 'Yes. Contact our team and we will help you set up your flats and begin billing from the current period.'],
            ],
            'related' => ['society-maintenance-software', 'society-maintenance-billing', 'apartment-management-software', 'contact'],
        ],

        // ------------------------------------------------ MAINTENANCE BILLING
        'society-maintenance-billing' => [
            'path' => '/society-maintenance-billing',
            'name' => 'Society Maintenance Billing',
            'anchor' => 'society maintenance billing',
            'title' => 'Society Maintenance Billing Software | FlatCare',
            'description' => 'Create maintenance bills, add water and extra charges, collect payments online and keep receipts with FlatCare society maintenance billing software.',
            'h1' => 'Society Maintenance Billing Software with Online Payment',
            'lead' => 'Maintenance billing is the heart of every society office and also its biggest source of manual work. FlatCare turns it into a clear routine: set the charges once, generate bills for every flat, collect payments and keep receipts in one place.',
            'sections' => [
                [
                    'h2' => 'How maintenance billing works in FlatCare',
                    'intro' => 'The flow is designed to match how societies already work.',
                    'items' => [
                        ['h3' => '1. Set charges', 'text' => 'Enter the fixed maintenance amount for your society and create fee types for other regular or one-off charges.'],
                        ['h3' => '2. Add readings and extras', 'text' => 'Record water readings and add any extra charges to specific flats when needed.'],
                        ['h3' => '3. Bills for every flat', 'text' => 'FlatCare prepares bills per flat so residents see exactly what they owe and why.'],
                        ['h3' => '4. Collect and record payments', 'text' => 'Residents can pay online through the app, and the office can record payments made in other ways.'],
                    ],
                ],
                [
                    'h2' => 'Maintenance fee collection you can trust',
                    'intro' => 'Collecting maintenance fees is easier when everyone can see the same numbers.',
                    'items' => [
                        ['h3' => 'Secure online payments', 'text' => 'Payments are confirmed with the payment gateway before a bill is marked as paid, so amounts cannot be changed by the app.'],
                        ['h3' => 'Receipts and invoices', 'text' => 'Every recorded payment has a receipt, and bills can be viewed or printed as an invoice.'],
                        ['h3' => 'Pending and overdue view', 'text' => 'Admins can list unpaid bills and follow up directly, which speeds up collection.'],
                    ],
                ],
                [
                    'h2' => 'Works with the rest of your society system',
                    'intro' => 'Billing connects to flats, residents and notifications.',
                    'items' => [
                        ['h3' => 'Right bill, right flat', 'text' => 'Because flats and residents are already in FlatCare, bills reach the correct household automatically.'],
                        ['h3' => 'Records for accounting', 'text' => 'Payment records give your accountant a clean trail. Learn more on the <a href="/society-accounting-software">society accounting software</a> page.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is society maintenance billing software?', 'It is software that prepares maintenance bills for every flat, records payments and shows outstanding dues, replacing manual bill books and spreadsheets.'],
                ['Can FlatCare bill water charges?', 'Yes. Water readings can be recorded, and the resulting charge can be added to the flat’s bill.'],
                ['Can residents pay maintenance online?', 'Yes. Residents can pay through the FlatCare app using an online payment gateway, and the payment is verified before the bill is marked as paid.'],
                ['Does FlatCare support apartment maintenance billing too?', 'Yes. The same billing works for apartment buildings and housing societies of different sizes.'],
                ['Are receipts available?', 'Yes. Receipts are available for recorded payments, and bills can be viewed or printed as invoices.'],
            ],
            'related' => ['society-accounting-software', 'society-maintenance-software', 'pricing', 'contact'],
        ],

        // ------------------------------------------------------ ACCOUNTING
        'society-accounting-software' => [
            'path' => '/society-accounting-software',
            'name' => 'Society Accounting Software',
            'anchor' => 'society accounting software',
            'title' => 'Society Accounting Software for Dues & Payments | FlatCare',
            'description' => 'FlatCare keeps society bills, payments, receipts and outstanding dues organised for every flat, giving committees a clean base for society accounting.',
            'h1' => 'Society Accounting Software for Dues, Payments and Receipts',
            'lead' => 'Good society accounting starts with accurate records of what was billed and what was received. FlatCare keeps that record for every flat, so the treasurer and committee can answer questions about dues and collections without digging through paperwork.',
            'sections' => [
                [
                    'h2' => 'Clean records of what was billed and paid',
                    'intro' => 'FlatCare focuses on the collection side of society finances.',
                    'items' => [
                        ['h3' => 'Bills and charges', 'text' => 'Maintenance, water and extra charges are recorded per flat with clear titles, so every rupee billed can be explained.'],
                        ['h3' => 'Payments and receipts', 'text' => 'Payments are logged with the amount, date and reference, and receipts are available for each.'],
                        ['h3' => 'Outstanding dues', 'text' => 'See unpaid and partly paid bills, so the treasurer knows the true position at any time.'],
                    ],
                ],
                [
                    'h2' => 'Helpful for the treasurer and committee',
                    'intro' => 'Transparent records reduce doubt and save time at every general meeting.',
                    'items' => [
                        ['h3' => 'Quick answers', 'text' => 'Check a flat’s history in seconds instead of searching old registers.'],
                        ['h3' => 'A trail for your accountant', 'text' => 'Consistent payment records make it easier for your chartered accountant to prepare the society’s books.'],
                        ['h3' => 'Extra charges made visible', 'text' => 'One-off charges are recorded like any other bill, which keeps special collections transparent.'],
                    ],
                ],
                [
                    'h2' => 'What FlatCare does and does not replace',
                    'intro' => 'We prefer to be clear about scope.',
                    'items' => [
                        ['h3' => 'Fee and dues management', 'text' => 'FlatCare handles billing, collection records and receipts. See <a href="/society-maintenance-billing">society maintenance billing</a> for details.'],
                        ['h3' => 'Full bookkeeping', 'text' => 'It does not replace the accounting software or professional advice used for statutory accounts and audits. If you need a particular expense or accounting workflow, contact us to see whether it fits.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is society accounting software?', 'It is software that helps a society record what has been billed, what has been paid and what is outstanding, usually along with receipts and reports for the committee and accountant.'],
                ['Does FlatCare do full accounting?', 'FlatCare records bills, payments and receipts for every flat, which forms the base of society accounting. It does not replace professional bookkeeping for statutory accounts.'],
                ['Can FlatCare track society expenses?', 'FlatCare currently focuses on charges and collections. If you need a specific expense workflow, contact us and we will explain what is available.'],
                ['Can the treasurer see each flat’s history?', 'Yes. Each flat has its own bills and payment records that can be reviewed at any time.'],
            ],
            'related' => ['society-maintenance-billing', 'society-management-software', 'pricing', 'contact'],
        ],

        // ------------------------------------------------ SOCIETY APP
        'society-management-app' => [
            'path' => '/society-management-app',
            'name' => 'Society Management App',
            'anchor' => 'society management app',
            'title' => 'Society Management App for Residents | FlatCare',
            'description' => 'The FlatCare society management app lets residents pay maintenance, raise complaints, approve visitors and read notices from their Android phone.',
            'h1' => 'Society Management App for Residents and Committees',
            'lead' => 'Residents want quick answers, not another phone call to the society office. The FlatCare society management app puts bills, visitor approvals, complaints and notices on their phone, while the committee keeps working from the web dashboard.',
            'sections' => [
                [
                    'h2' => 'What residents can do in the FlatCare app',
                    'intro' => 'Everyday society tasks are a few taps away.',
                    'items' => [
                        ['h3' => 'View and pay maintenance bills', 'text' => 'See what is due, pay online and keep receipts, without visiting the office.'],
                        ['h3' => 'Approve visitors and create gate passes', 'text' => 'Approve unexpected visitors, pre-approve guests and share a gate pass so security knows who to expect.'],
                        ['h3' => 'Raise and track complaints', 'text' => 'Report an issue with a few details and follow its status in the app.'],
                        ['h3' => 'Notices, events and polls', 'text' => 'Read announcements, see upcoming events and take part in society polls.'],
                        ['h3' => 'Directory and important contacts', 'text' => 'Find society contacts, service providers and emergency numbers quickly.'],
                    ],
                ],
                [
                    'h2' => 'Help when you need it',
                    'intro' => 'The app includes a help line page so residents can reach FlatCare customer support by call, WhatsApp or email.',
                    'items' => [
                        ['h3' => 'Simple to install', 'text' => 'FlatCare is available as an Android app. Download it from the link on our website and sign in with the mobile number your society registered.'],
                        ['h3' => 'Made for committees too', 'text' => 'Committees continue to manage billing, users and notices from the web dashboard. See <a href="/society-management-software">society management software</a>.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is a society management app?', 'It is a mobile app that lets residents interact with their housing society digitally: paying maintenance, approving visitors, raising complaints and reading notices.'],
                ['Is the FlatCare app available for Android?', 'Yes. You can download the FlatCare Android app from the download button on our website.'],
                ['How do residents sign in?', 'Residents sign in with the mobile number their society has registered for their flat.'],
                ['Can security guards use the app?', 'Yes. Guards registered by the society use a gate flow to record visitors and request resident approval.'],
            ],
            'related' => ['apartment-management-app', 'society-maintenance-software', 'society-management-software', 'contact'],
        ],

        // -------------------------------------------------- APARTMENT APP
        'apartment-management-app' => [
            'path' => '/apartment-management-app',
            'name' => 'Apartment Management App',
            'anchor' => 'apartment management app',
            'title' => 'Apartment Management App with Visitor Approval | FlatCare',
            'description' => 'Use the FlatCare apartment management app for visitor approval, gate passes, daily helpers, maintenance bills and complaints, right from your phone.',
            'h1' => 'Apartment Management App for Visitors, Bills and Complaints',
            'lead' => 'Life in an apartment is easier when visitors, bills and complaints are handled on your phone. The FlatCare apartment management app gives residents control over who enters, what they owe and what needs attention.',
            'sections' => [
                [
                    'h2' => 'Visitor management residents can control',
                    'intro' => 'Security is most effective when the resident stays in the loop.',
                    'items' => [
                        ['h3' => 'Approve or reject at the gate', 'text' => 'When a guest arrives unannounced, the guard raises a request and you decide from your phone.'],
                        ['h3' => 'Gate pass and pre-approval', 'text' => 'Create a gate pass for a visit with dates, or pre-approve someone you trust so entry is quick.'],
                        ['h3' => 'Daily helpers', 'text' => 'Keep your regular helpers such as maids, cooks and drivers listed in one place.'],
                        ['h3' => 'Your own visitor settings', 'text' => 'Choose to allow guests only with your approval, or mark your home as closed while you are away.'],
                    ],
                ],
                [
                    'h2' => 'Bills and complaints on the same app',
                    'intro' => 'The same app handles the rest of apartment life.',
                    'items' => [
                        ['h3' => 'Maintenance bills', 'text' => 'Check dues and pay online. Read more about <a href="/apartment-maintenance-management">apartment maintenance management</a>.'],
                        ['h3' => 'Complaints', 'text' => 'Raise an issue with your building and see when it is handled.'],
                        ['h3' => 'Notices', 'text' => 'Stay updated with announcements from the committee.'],
                    ],
                ],
                [
                    'h2' => 'A gate app for security staff',
                    'intro' => 'Guards get a simple screen to check visitors in and out, take a photo and request resident approval, so the register stays accurate.',
                    'items' => [
                        ['h3' => 'Fewer disputes', 'text' => 'A digital visitor log means fewer arguments about who entered and when.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is an apartment management app?', 'It is a mobile app that helps apartment residents manage visitors, maintenance bills, complaints and notices from their phone.'],
                ['Can I approve visitors from the FlatCare app?', 'Yes. When a guard requests entry for a visitor, you can approve or reject it in the app.'],
                ['Can I create a gate pass for a guest?', 'Yes. Residents can create a gate pass with visit dates and share the pass code with the visitor.'],
                ['Can I stop unexpected guests from entering?', 'Yes. Turn on “allow guests only if I approve”, or mark your house as closed while you are away.'],
            ],
            'related' => ['society-management-app', 'apartment-management-software', 'features', 'contact'],
        ],

        // ------------------------------------------------------ FEATURES
        'features' => [
            'path' => '/features',
            'name' => 'Features',
            'title' => 'FlatCare Features – Society & Apartment Management Tools',
            'description' => 'Explore FlatCare features: maintenance billing, payments, resident management, visitors, complaints, announcements, polls, documents and admin roles.',
            'h1' => 'FlatCare Features for Apartment and Society Management',
            'lead' => 'FlatCare brings the everyday work of a housing society into one system. Here is what you can do with it today, and where to read more.',
            'sections' => [
                [
                    'h2' => 'Finance and billing',
                    'intro' => 'Bill every flat and keep a clear record of collections.',
                    'items' => [
                        ['h3' => 'Maintenance billing', 'text' => 'Fixed maintenance, water charges and extra charges billed per flat. See <a href="/society-maintenance-billing">society maintenance billing</a>.'],
                        ['h3' => 'Online payments and receipts', 'text' => 'Residents can pay in the app; every recorded payment has a receipt.'],
                        ['h3' => 'Dues and records', 'text' => 'Track paid, pending and overdue bills. See <a href="/society-accounting-software">society accounting software</a>.'],
                    ],
                ],
                [
                    'h2' => 'People and communication',
                    'intro' => 'Keep residents informed and the committee organised.',
                    'items' => [
                        ['h3' => 'Resident and flat management', 'text' => 'Blocks, flats, owners, tenants, family members and vehicles in one directory.'],
                        ['h3' => 'Announcements and events', 'text' => 'Post notices and plan society events for everyone.'],
                        ['h3' => 'Polls and elections', 'text' => 'Collect opinions and run committee elections transparently.'],
                        ['h3' => 'Documents and contacts', 'text' => 'Share society documents, emergency contacts and service providers.'],
                    ],
                ],
                [
                    'h2' => 'Security and daily operations',
                    'intro' => 'A gate register that residents and guards can rely on.',
                    'items' => [
                        ['h3' => 'Visitor management', 'text' => 'Check-in and check-out records, visitor photos and resident approval.'],
                        ['h3' => 'Gate pass, pre-approval and daily helpers', 'text' => 'Residents prepare entries in advance from the app. See the <a href="/apartment-management-app">apartment management app</a>.'],
                        ['h3' => 'Security guard roster', 'text' => 'Keep track of guards and shifts on duty.'],
                        ['h3' => 'Complaints', 'text' => 'Residents raise issues; the committee tracks each one to completion.'],
                    ],
                ],
                [
                    'h2' => 'Access and administration',
                    'intro' => 'Control who can do what.',
                    'items' => [
                        ['h3' => 'Admin and committee roles', 'text' => 'Role-based access for admins, committee members, residents and security.'],
                        ['h3' => 'Web dashboard and mobile app', 'text' => 'Manage from the web; residents use the <a href="/society-management-app">society management app</a>.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What features does FlatCare have?', 'FlatCare includes maintenance billing, online payments and receipts, resident and flat management, visitor and gate management, complaints, announcements, events, polls, documents, service providers and role-based admin access.'],
                ['Is there a mobile app?', 'Yes. Residents can use the FlatCare Android app, and security guards have a gate flow inside the same app.'],
                ['Can I request a feature?', 'Yes. Contact us and tell us what your society needs.'],
            ],
            'related' => ['society-management-software', 'apartment-management-software', 'pricing', 'contact'],
        ],

        // ------------------------------------------------------- PRICING
        'pricing' => [
            'path' => '/pricing',
            'name' => 'Pricing',
            'title' => 'FlatCare Pricing – Free Trial for Housing Societies',
            'description' => 'See how FlatCare pricing works for apartments and housing societies. Start with a free trial and get a plan that fits your society’s size and needs.',
            'h1' => 'FlatCare Pricing for Apartments and Housing Societies',
            'lead' => 'Every society is different, so FlatCare is priced around the size of your community and the modules you need. Start with a free trial, see how it fits, and then choose a plan with our team.',
            'sections' => [
                [
                    'h2' => 'How FlatCare pricing works',
                    'intro' => 'We would rather quote fairly for your society than publish a one-size-fits-all number.',
                    'items' => [
                        ['h3' => 'Start with a free trial', 'text' => 'Request a trial and our team will set up your society so you can see real screens with your own blocks and flats. No credit card is required.'],
                        ['h3' => 'A plan that fits your society', 'text' => 'Pricing depends on the number of flats and the features you use, such as billing, visitor management and the resident app.'],
                        ['h3' => 'No surprises', 'text' => 'We will explain what is included before you commit, so the committee can decide with confidence.'],
                    ],
                ],
                [
                    'h2' => 'What every plan is built around',
                    'intro' => 'The goal of FlatCare is the same for every society.',
                    'items' => [
                        ['h3' => 'Billing and collections', 'text' => 'Maintenance bills, payments and receipts. See <a href="/society-maintenance-billing">society maintenance billing</a>.'],
                        ['h3' => 'Residents and gate', 'text' => 'Resident records, visitor management and complaints. See <a href="/features">all features</a>.'],
                        ['h3' => 'Support', 'text' => 'Help by phone, WhatsApp and email whenever your committee or residents need it.'],
                    ],
                ],
            ],
            'faqs' => [
                ['How much does FlatCare cost?', 'The price depends on the size of your society and the features you need. Request a free trial or contact us and we will share a quote for your society.'],
                ['Is there a free trial?', 'Yes. You can request a free trial from the website, and no credit card is required.'],
                ['Can a small apartment building use FlatCare?', 'Yes. FlatCare is suitable for small apartment buildings and large housing societies alike.'],
                ['How do we get started?', 'Send us your society details through the contact form and our team will get back to you.'],
            ],
            'related' => ['features', 'society-management-software', 'apartment-management-software', 'contact'],
        ],

        // --------------------------------------------------------- ABOUT
        'about' => [
            'path' => '/about',
            'name' => 'About',
            'title' => 'About FlatCare – Apartment & Society Management Software',
            'description' => 'FlatCare is apartment and society management software for India. Learn what FlatCare does, who it is for and how it helps communities run smoothly.',
            'h1' => 'About FlatCare',
            'lead' => 'FlatCare is apartment and society management software that helps housing societies, apartment associations and residential communities in India manage their daily operations with less paperwork and more clarity.',
            'sections' => [
                [
                    'h2' => 'What FlatCare does',
                    'intro' => 'FlatCare combines a web dashboard for society staff and a mobile app for residents.',
                    'items' => [
                        ['h3' => 'For committees and admins', 'text' => 'Billing, resident records, visitor logs, complaints and notices in one place. See <a href="/society-management-software">society management software</a>.'],
                        ['h3' => 'For residents', 'text' => 'Bills, visitor approvals, complaints and notices on their phone through the <a href="/society-management-app">society management app</a>.'],
                        ['h3' => 'For security staff', 'text' => 'A simple gate flow to record visitors and request approvals.'],
                    ],
                ],
                [
                    'h2' => 'What we believe',
                    'intro' => 'A society runs best when information is clear and shared.',
                    'items' => [
                        ['h3' => 'Simple by design', 'text' => 'Software for a society should be usable by committee members who are not technical.'],
                        ['h3' => 'Privacy and access control', 'text' => 'Residents’ details are visible only to the people who need them.'],
                        ['h3' => 'Honest about scope', 'text' => 'We describe what FlatCare does today and work with societies on what they need next.'],
                    ],
                ],
                [
                    'h2' => 'Talk to us',
                    'intro' => 'Questions, demos and feedback are always welcome.',
                    'items' => [
                        ['h3' => 'Contact FlatCare', 'text' => 'Reach us at support@flatcare.in or through the <a href="/contact">contact page</a>.'],
                    ],
                ],
            ],
            'faqs' => [
                ['What is FlatCare?', 'FlatCare is apartment and society management software for housing societies, apartments and RWAs in India, with a web dashboard and a mobile app.'],
                ['Who is FlatCare for?', 'Society committees and administrators, apartment managers, residents and security staff.'],
                ['How can I contact the FlatCare team?', 'Email support@flatcare.in or call or WhatsApp the numbers on the contact page.'],
            ],
            'related' => ['features', 'pricing', 'contact', 'society-management-software'],
        ],

        // ------------------------------------------------------- CONTACT
        'contact' => [
            'path' => '/contact',
            'name' => 'Contact',
            'title' => 'Contact FlatCare – Support and Free Trial Requests',
            'description' => 'Contact the FlatCare team for a free trial, a demo or support. Call or WhatsApp us, email support@flatcare.in, or send your society details.',
            'h1' => 'Contact FlatCare',
            'lead' => 'Want to see FlatCare for your society, or need help with the app? Reach the team by phone, WhatsApp or email, or send your society details and we will get in touch.',
            'sections' => [
                [
                    'h2' => 'Ways to reach us',
                    'intro' => 'Customer support is available round the clock by phone and WhatsApp.',
                    'items' => [],
                ],
            ],
            'faqs' => [
                ['How quickly will you respond to a trial request?', 'We aim to contact you within one business day of your request.'],
                ['Can I get a demo for my committee?', 'Yes. Send your society details and mention that you would like a demo, and we will arrange it.'],
                ['Where do I get help with the app?', 'Use the Help Line page inside the FlatCare app, or call or WhatsApp the numbers on this page.'],
            ],
            'related' => ['features', 'pricing', 'about'],
        ],
    ],
];
