<?php
/**
 * Example Page
 * Demonstrates the use of the template system
 */

// Start session
session_start();

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

// Set page variables
$page_title = 'Example Page';
$page_description = 'This is an example page demonstrating the Karyalay template system';

// Include header
include_header($page_title, $page_description);
?>

<div class="container">
    <!-- Hero Section Example -->
    <section class="hero">
        <div class="container">
            <h1 class="hero-title">Welcome to Karyalay</h1>
            <p class="hero-subtitle">
                <?php echo htmlspecialchars(get_footer_company_description()); ?>
            </p>
            <div class="hero-actions">
                <a href="<?php echo get_base_url(); ?>/register.php" class="btn btn-primary btn-lg">Get Started</a>
                <a href="<?php echo get_base_url(); ?>/demo.php" class="btn btn-outline btn-lg">Request Demo</a>
            </div>
        </div>
    </section>

    <!-- Components Showcase -->
    <section class="section">
        <h2 class="section-title">Design System Components</h2>
        
        <!-- Buttons -->
        <div class="mb-8">
            <h3 class="mb-4">Buttons</h3>
            <div class="flex gap-4 flex-wrap">
                <button class="btn btn-primary">Primary Button</button>
                <button class="btn btn-secondary">Secondary Button</button>
                <button class="btn btn-outline">Outline Button</button>
                <button class="btn btn-danger">Danger Button</button>
                <button class="btn btn-primary" disabled>Disabled Button</button>
            </div>
        </div>

        <!-- Button Sizes -->
        <div class="mb-8">
            <h3 class="mb-4">Button Sizes</h3>
            <div class="flex gap-4 flex-wrap items-center">
                <button class="btn btn-primary btn-sm">Small</button>
                <button class="btn btn-primary">Default</button>
                <button class="btn btn-primary btn-lg">Large</button>
            </div>
        </div>

        <!-- Cards -->
        <div class="mb-8">
            <h3 class="mb-4">Cards</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Card Title</h4>
                    </div>
                    <div class="card-body">
                        <p>This is a card component with header, body, and footer sections.</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary btn-sm">Action</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Another Card</h4>
                    </div>
                    <div class="card-body">
                        <p>Cards are great for displaying grouped content and information.</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-outline btn-sm">Learn More</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Third Card</h4>
                    </div>
                    <div class="card-body">
                        <p>They automatically adapt to different screen sizes.</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-secondary btn-sm">View Details</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms -->
        <div class="mb-8">
            <h3 class="mb-4">Form Elements</h3>
            <div class="card" style="max-width: 600px;">
                <form>
                    <div class="form-group">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" class="form-input" placeholder="Enter your name">
                        <span class="form-help">Please enter your full name</span>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">Message</label>
                        <textarea id="message" name="message" class="form-textarea" placeholder="Your message here..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="country" class="form-label">Country</label>
                        <select id="country" name="country" class="form-select">
                            <option value="">Select a country</option>
                            <option value="us">United States</option>
                            <option value="uk">United Kingdom</option>
                            <option value="ca">Canada</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Form</button>
                </form>
            </div>
        </div>

        <!-- Alerts -->
        <div class="mb-8">
            <h3 class="mb-4">Alerts</h3>
            <div class="alert alert-success">This is a success alert message!</div>
            <div class="alert alert-warning">This is a warning alert message!</div>
            <div class="alert alert-danger">This is a danger alert message!</div>
            <div class="alert alert-info">This is an info alert message!</div>
        </div>

        <!-- Badges -->
        <div class="mb-8">
            <h3 class="mb-4">Badges</h3>
            <div class="flex gap-3 flex-wrap">
                <span class="badge badge-primary">Primary</span>
                <span class="badge badge-success">Success</span>
                <span class="badge badge-warning">Warning</span>
                <span class="badge badge-danger">Danger</span>
                <span class="badge badge-secondary">Secondary</span>
            </div>
        </div>

        <!-- Table -->
        <div class="mb-8">
            <h3 class="mb-4">Table</h3>
            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>john@example.com</td>
                            <td>Admin</td>
                            <td><span class="badge badge-success">Active</span></td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td>jane@example.com</td>
                            <td>Customer</td>
                            <td><span class="badge badge-success">Active</span></td>
                        </tr>
                        <tr>
                            <td>Bob Johnson</td>
                            <td>bob@example.com</td>
                            <td>Customer</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php
// Include footer
include_footer();
?>
