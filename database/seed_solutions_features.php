<?php
/**
 * Seed Solutions and Features with realistic dummy data
 * Creates 5 solutions and 5 features with proper relationships
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

function generateUuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['database']['user'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connected to database successfully.\n\n";
    
    // Define 5 realistic features for an office management ERP
    $features = [
        [
            'id' => generateUuid(),
            'name' => 'Smart Document Management',
            'slug' => 'smart-document-management',
            'description' => 'Centralized document storage with AI-powered search, version control, and automated categorization. Never lose a file again.',
            'tagline' => 'Your documents, intelligently organized',
            'category' => 'Productivity',
            'is_core' => true,
            'color_accent' => '#3b82f6',
            'icon_image' => null,
            'benefits' => json_encode([
                ['title' => 'AI-Powered Search', 'description' => 'Find any document in seconds with intelligent search that understands context'],
                ['title' => 'Version Control', 'description' => 'Track every change with automatic versioning and rollback capabilities'],
                ['title' => 'Secure Sharing', 'description' => 'Share documents securely with granular permission controls'],
                ['title' => 'Cloud Sync', 'description' => 'Access your documents from anywhere with real-time synchronization']
            ]),
            'screenshots' => json_encode([]),
            'display_order' => 1,
            'status' => 'PUBLISHED'
        ],
        [
            'id' => generateUuid(),
            'name' => 'Employee Time Tracking',
            'slug' => 'employee-time-tracking',
            'description' => 'Comprehensive time tracking with automatic timesheets, project allocation, and productivity insights.',
            'tagline' => 'Track time, maximize productivity',
            'category' => 'HR & Workforce',
            'is_core' => true,
            'color_accent' => '#10b981',
            'icon_image' => null,
            'benefits' => json_encode([
                ['title' => 'Automatic Timesheets', 'description' => 'Generate accurate timesheets automatically from tracked activities'],
                ['title' => 'Project Allocation', 'description' => 'Allocate time across multiple projects with ease'],
                ['title' => 'Productivity Analytics', 'description' => 'Gain insights into team productivity patterns'],
                ['title' => 'Mobile Clock-In', 'description' => 'Clock in from anywhere with GPS verification']
            ]),
            'screenshots' => json_encode([]),
            'display_order' => 2,
            'status' => 'PUBLISHED'
        ],
        [
            'id' => generateUuid(),
            'name' => 'Expense Management',
            'slug' => 'expense-management',
            'description' => 'Streamline expense reporting with receipt scanning, automated approvals, and real-time budget tracking.',
            'tagline' => 'Expenses made effortless',
            'category' => 'Finance',
            'is_core' => true,
            'color_accent' => '#f59e0b',
            'icon_image' => null,
            'benefits' => json_encode([
                ['title' => 'Receipt Scanning', 'description' => 'Snap a photo and let AI extract all the details'],
                ['title' => 'Automated Approvals', 'description' => 'Set up approval workflows that match your policies'],
                ['title' => 'Budget Tracking', 'description' => 'Monitor spending against budgets in real-time'],
                ['title' => 'Reimbursement', 'description' => 'Process reimbursements quickly and accurately']
            ]),
            'screenshots' => json_encode([]),
            'display_order' => 3,
            'status' => 'PUBLISHED'
        ],
        [
            'id' => generateUuid(),
            'name' => 'Meeting Room Booking',
            'slug' => 'meeting-room-booking',
            'description' => 'Smart room scheduling with calendar integration, equipment management, and visitor notifications.',
            'tagline' => 'Book spaces, not headaches',
            'category' => 'Facilities',
            'is_core' => false,
            'color_accent' => '#8b5cf6',
            'icon_image' => null,
            'benefits' => json_encode([
                ['title' => 'Calendar Integration', 'description' => 'Sync with Google Calendar, Outlook, and more'],
                ['title' => 'Equipment Booking', 'description' => 'Reserve projectors, whiteboards, and other equipment'],
                ['title' => 'Visitor Management', 'description' => 'Notify reception when guests arrive for meetings'],
                ['title' => 'Usage Analytics', 'description' => 'Optimize space utilization with detailed reports']
            ]),
            'screenshots' => json_encode([]),
            'display_order' => 4,
            'status' => 'PUBLISHED'
        ],
        [
            'id' => generateUuid(),
            'name' => 'Task & Project Management',
            'slug' => 'task-project-management',
            'description' => 'Collaborative project management with Kanban boards, Gantt charts, and team workload balancing.',
            'tagline' => 'Projects delivered, on time',
            'category' => 'Productivity',
            'is_core' => true,
            'color_accent' => '#ec4899',
            'icon_image' => null,
            'benefits' => json_encode([
                ['title' => 'Kanban Boards', 'description' => 'Visualize workflow with drag-and-drop task boards'],
                ['title' => 'Gantt Charts', 'description' => 'Plan projects with interactive timeline views'],
                ['title' => 'Team Workload', 'description' => 'Balance work across team members effectively'],
                ['title' => 'Milestone Tracking', 'description' => 'Track progress against key project milestones']
            ]),
            'screenshots' => json_encode([]),
            'display_order' => 5,
            'status' => 'PUBLISHED'
        ]
    ];
    
    // Define 5 realistic solutions
    $solutions = [
        [
            'id' => generateUuid(),
            'name' => 'Complete Office Suite',
            'slug' => 'complete-office-suite',
            'description' => 'An all-in-one solution for modern offices. Manage documents, track time, handle expenses, and coordinate teams from a single platform.',
            'tagline' => 'Everything your office needs, unified',
            'hero_image' => null,
            'video_url' => null,
            'icon_image' => null,
            'features' => json_encode(['Document management', 'Time tracking', 'Expense reporting', 'Meeting scheduling', 'Project management']),
            'benefits' => json_encode([
                ['title' => 'Unified Platform', 'description' => 'All your office tools in one place, eliminating app switching'],
                ['title' => 'Cost Savings', 'description' => 'Replace multiple subscriptions with a single, affordable solution'],
                ['title' => 'Seamless Integration', 'description' => 'All features work together, sharing data automatically'],
                ['title' => '24/7 Support', 'description' => 'Round-the-clock support to keep your office running smoothly']
            ]),
            'use_cases' => json_encode([
                ['title' => 'Growing Startups', 'description' => 'Scale your operations without scaling complexity'],
                ['title' => 'Remote Teams', 'description' => 'Keep distributed teams connected and productive'],
                ['title' => 'Enterprise Offices', 'description' => 'Standardize processes across multiple locations']
            ]),
            'stats' => json_encode([
                ['value' => '40%', 'label' => 'Time Saved'],
                ['value' => '99.9%', 'label' => 'Uptime'],
                ['value' => '10K+', 'label' => 'Happy Users'],
                ['value' => '50+', 'label' => 'Integrations']
            ]),
            'color_theme' => '#667eea',
            'screenshots' => json_encode([]),
            'faqs' => json_encode([
                ['question' => 'How long does implementation take?', 'answer' => 'Most teams are up and running within 2-3 days. Our onboarding specialists guide you through every step.'],
                ['question' => 'Can I import data from other tools?', 'answer' => 'Yes! We support imports from all major office tools including Google Workspace, Microsoft 365, and more.'],
                ['question' => 'Is my data secure?', 'answer' => 'Absolutely. We use bank-level encryption and are SOC 2 Type II certified.']
            ]),
            'display_order' => 1,
            'status' => 'PUBLISHED',
            'featured_on_homepage' => true,
            'linked_features' => [0, 1, 2, 3, 4] // All features
        ],
        [
            'id' => generateUuid(),
            'name' => 'HR & Workforce Management',
            'slug' => 'hr-workforce-management',
            'description' => 'Streamline your HR operations with comprehensive workforce management tools. From time tracking to expense management, empower your HR team.',
            'tagline' => 'Empower your people, simplify HR',
            'hero_image' => null,
            'video_url' => null,
            'icon_image' => null,
            'features' => json_encode(['Time tracking', 'Expense management', 'Leave management', 'Performance reviews']),
            'benefits' => json_encode([
                ['title' => 'Automated Workflows', 'description' => 'Reduce manual HR tasks by 60% with smart automation'],
                ['title' => 'Employee Self-Service', 'description' => 'Empower employees to manage their own requests'],
                ['title' => 'Compliance Ready', 'description' => 'Stay compliant with built-in labor law features'],
                ['title' => 'Analytics Dashboard', 'description' => 'Make data-driven HR decisions with real-time insights']
            ]),
            'use_cases' => json_encode([
                ['title' => 'HR Departments', 'description' => 'Centralize all HR operations in one platform'],
                ['title' => 'Managers', 'description' => 'Approve requests and track team performance easily'],
                ['title' => 'Employees', 'description' => 'Submit requests and track time from anywhere']
            ]),
            'stats' => json_encode([
                ['value' => '60%', 'label' => 'Less Admin Work'],
                ['value' => '95%', 'label' => 'Approval Speed'],
                ['value' => '5K+', 'label' => 'Companies'],
                ['value' => '4.8', 'label' => 'User Rating']
            ]),
            'color_theme' => '#10b981',
            'screenshots' => json_encode([]),
            'faqs' => json_encode([
                ['question' => 'Does it integrate with payroll systems?', 'answer' => 'Yes, we integrate with major payroll providers including ADP, Gusto, and QuickBooks.'],
                ['question' => 'Can employees access it on mobile?', 'answer' => 'Our mobile app is available for iOS and Android with full functionality.']
            ]),
            'display_order' => 2,
            'status' => 'PUBLISHED',
            'featured_on_homepage' => true,
            'linked_features' => [1, 2] // Time tracking, Expense management
        ],
        [
            'id' => generateUuid(),
            'name' => 'Facility Management',
            'slug' => 'facility-management',
            'description' => 'Optimize your workspace with smart facility management. Book rooms, manage visitors, and track space utilization effortlessly.',
            'tagline' => 'Smart spaces, happy teams',
            'hero_image' => null,
            'video_url' => null,
            'icon_image' => null,
            'features' => json_encode(['Room booking', 'Visitor management', 'Space analytics', 'Equipment tracking']),
            'benefits' => json_encode([
                ['title' => 'Space Optimization', 'description' => 'Maximize office space utilization with data-driven insights'],
                ['title' => 'Seamless Booking', 'description' => 'Book rooms in seconds with calendar integration'],
                ['title' => 'Visitor Experience', 'description' => 'Create a professional first impression for guests'],
                ['title' => 'Cost Reduction', 'description' => 'Identify underutilized spaces and reduce real estate costs']
            ]),
            'use_cases' => json_encode([
                ['title' => 'Office Managers', 'description' => 'Manage facilities efficiently from one dashboard'],
                ['title' => 'Hybrid Workplaces', 'description' => 'Coordinate hot desking and flexible seating'],
                ['title' => 'Multi-Location', 'description' => 'Manage facilities across multiple office locations']
            ]),
            'stats' => json_encode([
                ['value' => '30%', 'label' => 'Space Savings'],
                ['value' => '2min', 'label' => 'Avg Booking Time'],
                ['value' => '1M+', 'label' => 'Bookings/Month'],
                ['value' => '98%', 'label' => 'Satisfaction']
            ]),
            'color_theme' => '#8b5cf6',
            'screenshots' => json_encode([]),
            'faqs' => json_encode([
                ['question' => 'Can visitors check in themselves?', 'answer' => 'Yes, our self-service kiosk mode allows visitors to check in and notify hosts automatically.'],
                ['question' => 'Does it work with access control systems?', 'answer' => 'We integrate with major access control systems for seamless entry management.']
            ]),
            'display_order' => 3,
            'status' => 'PUBLISHED',
            'featured_on_homepage' => true,
            'linked_features' => [3] // Meeting room booking
        ],
        [
            'id' => generateUuid(),
            'name' => 'Project Delivery Suite',
            'slug' => 'project-delivery-suite',
            'description' => 'Deliver projects on time and within budget. Comprehensive project management with resource planning, time tracking, and collaboration tools.',
            'tagline' => 'From kickoff to delivery, simplified',
            'hero_image' => null,
            'video_url' => null,
            'icon_image' => null,
            'features' => json_encode(['Project management', 'Time tracking', 'Document collaboration', 'Resource planning']),
            'benefits' => json_encode([
                ['title' => 'Visual Planning', 'description' => 'Plan projects with intuitive Gantt charts and Kanban boards'],
                ['title' => 'Resource Management', 'description' => 'Allocate team members effectively across projects'],
                ['title' => 'Real-time Collaboration', 'description' => 'Work together seamlessly with built-in communication'],
                ['title' => 'Budget Tracking', 'description' => 'Monitor project costs and profitability in real-time']
            ]),
            'use_cases' => json_encode([
                ['title' => 'Project Managers', 'description' => 'Plan, execute, and deliver projects successfully'],
                ['title' => 'Creative Agencies', 'description' => 'Manage client projects and creative workflows'],
                ['title' => 'IT Teams', 'description' => 'Track sprints, releases, and technical projects']
            ]),
            'stats' => json_encode([
                ['value' => '25%', 'label' => 'Faster Delivery'],
                ['value' => '90%', 'label' => 'On-Time Rate'],
                ['value' => '3K+', 'label' => 'Projects/Day'],
                ['value' => '15%', 'label' => 'Cost Savings']
            ]),
            'color_theme' => '#ec4899',
            'screenshots' => json_encode([]),
            'faqs' => json_encode([
                ['question' => 'Can I use agile methodologies?', 'answer' => 'Absolutely! We support Scrum, Kanban, and hybrid approaches with customizable workflows.'],
                ['question' => 'How does resource planning work?', 'answer' => 'View team availability, skills, and workload to make optimal assignment decisions.']
            ]),
            'display_order' => 4,
            'status' => 'PUBLISHED',
            'featured_on_homepage' => false,
            'linked_features' => [0, 1, 4] // Document management, Time tracking, Task management
        ],
        [
            'id' => generateUuid(),
            'name' => 'Finance & Expense Control',
            'slug' => 'finance-expense-control',
            'description' => 'Take control of your business finances. Automate expense reporting, enforce spending policies, and gain visibility into every dollar.',
            'tagline' => 'Every expense, under control',
            'hero_image' => null,
            'video_url' => null,
            'icon_image' => null,
            'features' => json_encode(['Expense management', 'Budget tracking', 'Invoice processing', 'Financial reporting']),
            'benefits' => json_encode([
                ['title' => 'Policy Enforcement', 'description' => 'Automatically enforce spending policies and limits'],
                ['title' => 'Receipt Capture', 'description' => 'Capture receipts instantly with mobile scanning'],
                ['title' => 'Approval Workflows', 'description' => 'Streamline approvals with customizable workflows'],
                ['title' => 'Financial Insights', 'description' => 'Understand spending patterns with detailed analytics']
            ]),
            'use_cases' => json_encode([
                ['title' => 'Finance Teams', 'description' => 'Streamline expense processing and reporting'],
                ['title' => 'Traveling Employees', 'description' => 'Submit expenses on-the-go with mobile app'],
                ['title' => 'Budget Owners', 'description' => 'Monitor and control departmental spending']
            ]),
            'stats' => json_encode([
                ['value' => '80%', 'label' => 'Faster Processing'],
                ['value' => '$50K', 'label' => 'Avg Savings/Year'],
                ['value' => '99%', 'label' => 'Accuracy'],
                ['value' => '24hr', 'label' => 'Reimbursement']
            ]),
            'color_theme' => '#f59e0b',
            'screenshots' => json_encode([]),
            'faqs' => json_encode([
                ['question' => 'Can I set spending limits?', 'answer' => 'Yes, set limits by employee, department, category, or project with automatic enforcement.'],
                ['question' => 'How are receipts processed?', 'answer' => 'Our AI extracts data from receipts automatically, reducing manual entry by 95%.']
            ]),
            'display_order' => 5,
            'status' => 'PUBLISHED',
            'featured_on_homepage' => false,
            'linked_features' => [2] // Expense management
        ]
    ];
    
    // Insert features
    echo "Inserting features...\n";
    $featureStmt = $pdo->prepare("
        INSERT INTO features (id, name, slug, description, tagline, category, is_core, color_accent, icon_image, benefits, screenshots, display_order, status)
        VALUES (:id, :name, :slug, :description, :tagline, :category, :is_core, :color_accent, :icon_image, :benefits, :screenshots, :display_order, :status)
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            description = VALUES(description),
            tagline = VALUES(tagline),
            category = VALUES(category),
            is_core = VALUES(is_core),
            color_accent = VALUES(color_accent),
            benefits = VALUES(benefits)
    ");
    
    $featureIds = [];
    foreach ($features as $feature) {
        $featureStmt->execute([
            ':id' => $feature['id'],
            ':name' => $feature['name'],
            ':slug' => $feature['slug'],
            ':description' => $feature['description'],
            ':tagline' => $feature['tagline'],
            ':category' => $feature['category'],
            ':is_core' => $feature['is_core'] ? 1 : 0,
            ':color_accent' => $feature['color_accent'],
            ':icon_image' => $feature['icon_image'],
            ':benefits' => $feature['benefits'],
            ':screenshots' => $feature['screenshots'],
            ':display_order' => $feature['display_order'],
            ':status' => $feature['status']
        ]);
        $featureIds[] = $feature['id'];
        echo "  ✓ Feature: {$feature['name']}\n";
    }
    
    // Insert solutions
    echo "\nInserting solutions...\n";
    $solutionStmt = $pdo->prepare("
        INSERT INTO solutions (id, name, slug, description, tagline, hero_image, video_url, icon_image, features, benefits, use_cases, stats, color_theme, screenshots, faqs, display_order, status, featured_on_homepage)
        VALUES (:id, :name, :slug, :description, :tagline, :hero_image, :video_url, :icon_image, :features, :benefits, :use_cases, :stats, :color_theme, :screenshots, :faqs, :display_order, :status, :featured_on_homepage)
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            description = VALUES(description),
            tagline = VALUES(tagline),
            benefits = VALUES(benefits),
            use_cases = VALUES(use_cases),
            stats = VALUES(stats),
            color_theme = VALUES(color_theme),
            faqs = VALUES(faqs)
    ");
    
    $solutionIds = [];
    foreach ($solutions as $solution) {
        $linkedFeatures = $solution['linked_features'];
        unset($solution['linked_features']);
        
        $solutionStmt->execute([
            ':id' => $solution['id'],
            ':name' => $solution['name'],
            ':slug' => $solution['slug'],
            ':description' => $solution['description'],
            ':tagline' => $solution['tagline'],
            ':hero_image' => $solution['hero_image'],
            ':video_url' => $solution['video_url'],
            ':icon_image' => $solution['icon_image'],
            ':features' => $solution['features'],
            ':benefits' => $solution['benefits'],
            ':use_cases' => $solution['use_cases'],
            ':stats' => $solution['stats'],
            ':color_theme' => $solution['color_theme'],
            ':screenshots' => $solution['screenshots'],
            ':faqs' => $solution['faqs'],
            ':display_order' => $solution['display_order'],
            ':status' => $solution['status'],
            ':featured_on_homepage' => $solution['featured_on_homepage'] ? 1 : 0
        ]);
        
        $solutionIds[] = ['id' => $solution['id'], 'linked_features' => $linkedFeatures];
        echo "  ✓ Solution: {$solution['name']}\n";
    }
    
    // Create solution-feature links
    echo "\nCreating solution-feature relationships...\n";
    $linkStmt = $pdo->prepare("
        INSERT INTO solution_features (id, solution_id, feature_id, display_order, is_highlighted)
        VALUES (:id, :solution_id, :feature_id, :display_order, :is_highlighted)
        ON DUPLICATE KEY UPDATE display_order = VALUES(display_order)
    ");
    
    foreach ($solutionIds as $solutionData) {
        $order = 1;
        foreach ($solutionData['linked_features'] as $featureIndex) {
            $linkStmt->execute([
                ':id' => generateUuid(),
                ':solution_id' => $solutionData['id'],
                ':feature_id' => $featureIds[$featureIndex],
                ':display_order' => $order,
                ':is_highlighted' => $order <= 2 ? 1 : 0
            ]);
            $order++;
        }
    }
    echo "  ✓ Created all solution-feature links\n";
    
    echo "\n✅ Seeding completed successfully!\n";
    echo "   - 5 Features created\n";
    echo "   - 5 Solutions created\n";
    echo "   - Solution-Feature relationships established\n";
    
} catch (PDOException $e) {
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
