<?php
// Replace these values with your real details.
define('SITE_NAME', 'YourName.dev');
define('SITE_TAGLINE', 'Freelance Full-Stack Developer');
define('SITE_AVAILABILITY', 'Available for freelance projects');
define('SITE_EMAIL', 'hello@yourdomain.com');
define('SITE_PHONE', '+91 XXXXX XXXXX');
define('SITE_PHONE_RAW', '+91XXXXXXXXXX');
define('SITE_LOCATION', 'India — Working Worldwide');
define('SITE_GITHUB', 'https://github.com/');
define('SITE_LINKEDIN', 'https://www.linkedin.com/');
define('SITE_TWITTER', 'https://x.com/');

// Default administrator credentials (change before using the site publicly).
define('ADMIN_EMAIL', 'admin@yourdomain.com');
define('ADMIN_PASSWORD', 'admin');

function services(): array {
    return [
        ['icon'=>'⌘','title'=>'Web Development','description'=>'Fast, responsive websites designed to establish credibility and generate leads.'],
        ['icon'=>'◈','title'=>'Full-Stack Development','description'=>'Frontend, backend, database, authentication and API solutions built together.'],
        ['icon'=>'▣','title'=>'Business Websites','description'=>'Professional websites tailored around your brand, audience and business goals.'],
        ['icon'=>'◇','title'=>'E-Commerce','description'=>'Custom shopping experiences with products, payments and scalable architecture.'],
        ['icon'=>'⌁','title'=>'API & Backend','description'=>'Secure APIs, integrations, backend systems and database architecture.'],
        ['icon'=>'↗','title'=>'Optimization','description'=>'Improve performance, accessibility, SEO, UX and overall website quality.'],
    ];
}
function benefits(): array {
    return [
        ['Clean & maintainable code','Easy to extend and maintain as your business grows.'],
        ['Responsive by default','A polished experience across phones, tablets and desktops.'],
        ['Performance focused','Fast pages and sensible architecture without unnecessary complexity.'],
        ['Secure development','Validation, safe database access and security-minded implementation.'],
        ['Clear communication','Straightforward updates and practical technical decisions.'],
        ['Post-launch support','Help with fixes, improvements and ongoing maintenance.'],
    ];
}
function process_steps(): array {
    return [
        ['01','Discovery','Understand the business, users, goals and requirements.'],
        ['02','Planning','Define scope, features, technology and architecture.'],
        ['03','Design','Shape a clear, usable interface and user experience.'],
        ['04','Development','Build, integrate and test the actual product.'],
        ['05','Testing','Check functionality, responsiveness, performance and security.'],
        ['06','Launch','Deploy the project and provide post-launch support.'],
    ];
}
function projects(): array {
    return [
        ['title'=>'FinTech Dashboard','tag'=>'FINTECH','class'=>'art-one','description'=>'A clean analytics dashboard for monitoring business and financial metrics.','tech'=>['PHP','MySQL','JS']],
        ['title'=>'SaaS Management Platform','tag'=>'SAAS','class'=>'art-two','description'=>'A responsive management interface designed for teams and growing businesses.','tech'=>['PHP','MySQL','API']],
        ['title'=>'E-Commerce Platform','tag'=>'E-COMMERCE','class'=>'art-three','description'=>'A conversion-focused shopping experience with a scalable product structure.','tech'=>['PHP','MySQL','Payments']],
        ['title'=>'Security Dashboard','tag'=>'SECURITY','class'=>'art-four','description'=>'A dark operational dashboard for security events and application monitoring.','tech'=>['PHP','API','Charts']],
    ];
}
function technologies(): array {
    return ['PHP','MySQL','JavaScript','HTML5','CSS3','Bootstrap','Tailwind CSS','React','Node.js','Python','REST APIs','Git','GitHub','Docker','AWS'];
}
function testimonials(): array {
    return [
        ['quote'=>'A placeholder for a genuine client testimonial about communication, quality and delivery.','name'=>'Client Name','company'=>'Company Name'],
        ['quote'=>'Replace this demo text with real feedback from a satisfied client after a completed project.','name'=>'Client Name','company'=>'Company Name'],
        ['quote'=>'Use authentic client feedback here to build trust with future project leads.','name'=>'Client Name','company'=>'Company Name'],
    ];
}
function faqs(): array {
    return [
        ['How does the development process work?','We start with discovery and scope, then plan, design, develop, test and launch the project.'],
        ['How much does a website cost?','Pricing depends on scope, functionality, integrations and timeline. Submit your requirements for a tailored quote.'],
        ['How long does a project take?','Small websites can be completed quickly, while custom applications take longer depending on scope.'],
        ['What type of projects do you accept?','Websites, web applications, full-stack systems, e-commerce, APIs and custom software projects.'],
        ['Can you work on an existing website?','Yes. Existing projects can be audited, fixed, redesigned, optimized or extended.'],
        ['Do you provide maintenance?','Yes. Ongoing maintenance and post-launch improvements can be arranged.'],
        ['Can you sign an NDA?','Yes, an NDA can be discussed when the project requires confidentiality.'],
    ];
}
function budgets(): array { return ['Under ₹25,000','₹25,000 – ₹50,000','₹50,000 – ₹1,00,000','₹1,00,000 – ₹2,50,000','₹2,50,000+','Not sure yet']; }
function timelines(): array { return ['ASAP','1–2 weeks','1 month','2–3 months','Flexible']; }
