<?php

namespace Karyalay\Database;

use PDO;

/**
 * Database Seeder Class
 * 
 * Populates database with sample data for development
 */
class Seeder
{
    private PDO $pdo;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Generate a UUID v4
     * 
     * @return string
     */
    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Run all seeders
     * 
     * @return void
     */
    public function runAll(): void
    {
        echo "Seeding users...\n";
        $userIds = $this->seedUsers();
        
        echo "Seeding plans...\n";
        $planIds = $this->seedPlans();
        
        echo "Seeding ports...\n";
        $this->seedPorts($planIds);
        
        echo "Seeding modules...\n";
        $this->seedModules();
        
        echo "Seeding features...\n";
        $this->seedFeatures();
        
        echo "Seeding blog posts...\n";
        $this->seedBlogPosts($userIds);
        
        echo "Seeding case studies...\n";
        $this->seedCaseStudies();
        
        echo "Seeding leads...\n";
        $this->seedLeads();
        
        echo "\nSeeding complete!\n";
    }

    /**
     * Seed users table
     * 
     * @return array User IDs
     */
    private function seedUsers(): array
    {
        $users = [
            [
                'id' => $this->uuid(),
                'email' => 'admin@karyalay.com',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]),
                'name' => 'Admin User',
                'phone' => '+1234567890',
                'role' => 'ADMIN',
                'email_verified' => 1
            ],
            [
                'id' => $this->uuid(),
                'email' => 'customer@example.com',
                'password_hash' => password_hash('customer123', PASSWORD_BCRYPT, ['cost' => 12]),
                'name' => 'John Doe',
                'phone' => '+1234567891',
                'business_name' => 'Acme Corp',
                'role' => 'CUSTOMER',
                'email_verified' => 1
            ],
            [
                'id' => $this->uuid(),
                'email' => 'support@karyalay.com',
                'password_hash' => password_hash('support123', PASSWORD_BCRYPT, ['cost' => 12]),
                'name' => 'Support Agent',
                'phone' => '+1234567892',
                'role' => 'SUPPORT',
                'email_verified' => 1
            ]
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (id, email, password_hash, name, phone, business_name, role, email_verified) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $userIds = [];
        foreach ($users as $user) {
            $stmt->execute([
                $user['id'],
                $user['email'],
                $user['password_hash'],
                $user['name'],
                $user['phone'],
                $user['business_name'] ?? null,
                $user['role'],
                $user['email_verified']
            ]);
            $userIds[$user['role']] = $user['id'];
        }

        return $userIds;
    }

    /**
     * Seed plans table
     * 
     * @return array Plan IDs
     */
    private function seedPlans(): array
    {
        $plans = [
            [
                'id' => $this->uuid(),
                'name' => 'Basic Plan',
                'slug' => 'basic',
                'description' => 'Perfect for small businesses getting started',
                'mrp' => 29.99,
                'discounted_price' => null,
                'currency' => 'USD',
                'billing_period_months' => 1,
                'features_html' => '<ul><li>5 Users</li><li>10GB Storage</li><li>Email Support</li></ul>',
                'status' => 'ACTIVE'
            ],
            [
                'id' => $this->uuid(),
                'name' => 'Professional Plan',
                'slug' => 'professional',
                'description' => 'For growing businesses with advanced needs',
                'mrp' => 79.99,
                'discounted_price' => null,
                'currency' => 'USD',
                'billing_period_months' => 1,
                'features_html' => '<ul><li>25 Users</li><li>100GB Storage</li><li>Priority Support</li><li>Advanced Analytics</li></ul>',
                'status' => 'ACTIVE'
            ],
            [
                'id' => $this->uuid(),
                'name' => 'Enterprise Plan',
                'slug' => 'enterprise',
                'description' => 'For large organizations requiring maximum capacity',
                'mrp' => 199.99,
                'discounted_price' => null,
                'currency' => 'USD',
                'billing_period_months' => 1,
                'features_html' => '<ul><li>Unlimited Users</li><li>1TB Storage</li><li>24/7 Support</li><li>Custom Integration</li><li>Dedicated Account Manager</li></ul>',
                'status' => 'ACTIVE'
            ]
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO plans (id, name, slug, description, mrp, discounted_price, currency, billing_period_months, features_html, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $planIds = [];
        foreach ($plans as $plan) {
            $stmt->execute([
                $plan['id'],
                $plan['name'],
                $plan['slug'],
                $plan['description'],
                $plan['mrp'],
                $plan['discounted_price'],
                $plan['currency'],
                $plan['billing_period_months'],
                $plan['features_html'],
                $plan['status']
            ]);
            $planIds[$plan['slug']] = $plan['id'];
        }

        return $planIds;
    }

    /**
     * Seed ports table
     * 
     * @param array $planIds Plan IDs
     * @return void
     */
    private function seedPorts(array $planIds): void
    {
        $ports = [];
        
        // Create 5 ports for each plan
        foreach ($planIds as $slug => $planId) {
            for ($i = 1; $i <= 5; $i++) {
                $ports[] = [
                    'id' => $this->uuid(),
                    'instance_url' => "https://{$slug}-instance-{$i}.karyalay.com",
                    'db_host' => 'localhost',
                    'db_name' => "{$slug}_db_{$i}",
                    'db_username' => "{$slug}_user_{$i}",
                    'db_password' => 'demo_password_' . bin2hex(random_bytes(4)),
                    'plan_id' => $planId,
                    'status' => 'AVAILABLE',
                    'server_region' => 'us-east-1'
                ];
            }
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO ports (id, instance_url, db_host, db_name, db_username, db_password, plan_id, status, server_region) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($ports as $port) {
            $stmt->execute([
                $port['id'],
                $port['instance_url'],
                $port['db_host'],
                $port['db_name'],
                $port['db_username'],
                $port['db_password'],
                $port['plan_id'],
                $port['status'],
                $port['server_region']
            ]);
        }
    }

    /**
     * Seed modules table
     * 
     * @return void
     */
    private function seedModules(): void
    {
        $modules = [
            [
                'id' => $this->uuid(),
                'name' => 'Customer Management',
                'slug' => 'customer-management',
                'description' => 'Comprehensive customer relationship management tools',
                'features' => json_encode(['Contact Management', 'Activity Tracking', 'Custom Fields', 'Segmentation']),
                'screenshots' => json_encode([]),
                'faqs' => json_encode([
                    ['question' => 'How many contacts can I store?', 'answer' => 'Unlimited contacts on all plans'],
                    ['question' => 'Can I import existing contacts?', 'answer' => 'Yes, via CSV or API']
                ]),
                'display_order' => 1,
                'status' => 'PUBLISHED'
            ],
            [
                'id' => $this->uuid(),
                'name' => 'Inventory Management',
                'slug' => 'inventory-management',
                'description' => 'Track and manage your inventory in real-time',
                'features' => json_encode(['Stock Tracking', 'Low Stock Alerts', 'Barcode Scanning', 'Multi-location Support']),
                'screenshots' => json_encode([]),
                'faqs' => json_encode([
                    ['question' => 'Does it support multiple warehouses?', 'answer' => 'Yes, unlimited locations'],
                    ['question' => 'Can I track serial numbers?', 'answer' => 'Yes, full serial number tracking']
                ]),
                'display_order' => 2,
                'status' => 'PUBLISHED'
            ]
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO solutions (id, name, slug, description, features, screenshots, faqs, display_order, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($modules as $module) {
            $stmt->execute([
                $module['id'],
                $module['name'],
                $module['slug'],
                $module['description'],
                $module['features'],
                $module['screenshots'],
                $module['faqs'],
                $module['display_order'],
                $module['status']
            ]);
        }
    }

    /**
     * Seed features table
     * 
     * @return void
     */
    private function seedFeatures(): void
    {
        $features = [
            [
                'id' => $this->uuid(),
                'name' => 'Advanced Reporting',
                'slug' => 'advanced-reporting',
                'description' => 'Generate detailed reports and analytics',
                'benefits' => json_encode(['Data-driven decisions', 'Custom report builder', 'Export to multiple formats']),
                'related_solutions' => json_encode(['customer-management', 'inventory-management']),
                'screenshots' => json_encode([]),
                'display_order' => 1,
                'status' => 'PUBLISHED'
            ],
            [
                'id' => $this->uuid(),
                'name' => 'Mobile Access',
                'slug' => 'mobile-access',
                'description' => 'Access your data from anywhere on any device',
                'benefits' => json_encode(['iOS and Android apps', 'Offline mode', 'Real-time sync']),
                'related_solutions' => json_encode([]),
                'screenshots' => json_encode([]),
                'display_order' => 2,
                'status' => 'PUBLISHED'
            ]
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO features (id, name, slug, description, benefits, related_solutions, screenshots, display_order, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($features as $feature) {
            $stmt->execute([
                $feature['id'],
                $feature['name'],
                $feature['slug'],
                $feature['description'],
                $feature['benefits'],
                $feature['related_solutions'],
                $feature['screenshots'],
                $feature['display_order'],
                $feature['status']
            ]);
        }
    }

    /**
     * Seed blog posts table
     * 
     * @param array $userIds User IDs
     * @return void
     */
    private function seedBlogPosts(array $userIds): void
    {
        $posts = [
            [
                'id' => $this->uuid(),
                'title' => 'Getting Started with Karyalay',
                'slug' => 'getting-started-with-karyalay',
                'content' => 'Welcome to Karyalay! This guide will help you get started with our platform...',
                'excerpt' => 'Learn the basics of Karyalay in this comprehensive guide',
                'author_id' => $userIds['ADMIN'],
                'tags' => json_encode(['tutorial', 'getting-started']),
                'status' => 'PUBLISHED',
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $this->uuid(),
                'title' => '5 Tips for Better Customer Management',
                'slug' => '5-tips-for-better-customer-management',
                'content' => 'Effective customer management is crucial for business success...',
                'excerpt' => 'Improve your customer relationships with these proven strategies',
                'author_id' => $userIds['ADMIN'],
                'tags' => json_encode(['tips', 'customer-management']),
                'status' => 'PUBLISHED',
                'published_at' => date('Y-m-d H:i:s', strtotime('-7 days'))
            ]
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO blog_posts (id, title, slug, content, excerpt, author_id, tags, status, published_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($posts as $post) {
            $stmt->execute([
                $post['id'],
                $post['title'],
                $post['slug'],
                $post['content'],
                $post['excerpt'],
                $post['author_id'],
                $post['tags'],
                $post['status'],
                $post['published_at']
            ]);
        }
    }

    /**
     * Seed case studies table
     * 
     * @return void
     */
    private function seedCaseStudies(): void
    {
        $caseStudies = [
            [
                'id' => $this->uuid(),
                'title' => 'How Acme Corp Increased Efficiency by 40%',
                'slug' => 'acme-corp-efficiency',
                'client_name' => 'Acme Corporation',
                'industry' => 'Manufacturing',
                'challenge' => 'Acme Corp was struggling with manual inventory tracking and customer management',
                'solution' => 'Implemented Karyalay with Customer Management and Inventory Management modules',
                'results' => '40% increase in operational efficiency, 60% reduction in data entry time',
                'modules_used' => json_encode(['customer-management', 'inventory-management']),
                'status' => 'PUBLISHED'
            ]
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO case_studies (id, title, slug, client_name, industry, challenge, solution, results, modules_used, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($caseStudies as $study) {
            $stmt->execute([
                $study['id'],
                $study['title'],
                $study['slug'],
                $study['client_name'],
                $study['industry'],
                $study['challenge'],
                $study['solution'],
                $study['results'],
                $study['modules_used'],
                $study['status']
            ]);
        }
    }

    /**
     * Seed leads table
     * 
     * @return void
     */
    private function seedLeads(): void
    {
        $leads = [
            [
                'id' => $this->uuid(),
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'phone' => '+1234567893',
                'message' => 'I am interested in learning more about your platform',
                'source' => 'CONTACT_FORM',
                'status' => 'NEW'
            ],
            [
                'id' => $this->uuid(),
                'name' => 'Bob Johnson',
                'email' => 'bob.johnson@example.com',
                'phone' => '+1234567894',
                'message' => 'Would like to schedule a demo',
                'source' => 'DEMO_REQUEST',
                'status' => 'NEW',
                'company_name' => 'Tech Solutions Inc',
                'preferred_date' => date('Y-m-d', strtotime('+3 days'))
            ]
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO leads (id, name, email, phone, message, source, status, company_name, preferred_date) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($leads as $lead) {
            $stmt->execute([
                $lead['id'],
                $lead['name'],
                $lead['email'],
                $lead['phone'],
                $lead['message'],
                $lead['source'],
                $lead['status'],
                $lead['company_name'] ?? null,
                $lead['preferred_date'] ?? null
            ]);
        }
    }
}
