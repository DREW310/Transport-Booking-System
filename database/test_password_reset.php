<?php
/*
===========================================
FILE: Test Simple Password Reset (test_password_reset.php)
PURPOSE: Demonstrate the simple password reset functionality
STUDENT LEARNING: Testing password reset without email complexity
TECHNOLOGIES: PHP, MySQL, PDO
===========================================
*/

require_once('../includes/db.php');

try {
    $db = getDB();
    
    echo "<h2>🔑 Simple Password Reset Test</h2>\n";
    echo "<style>
        .test-section { margin: 2rem 0; padding: 1rem; border-left: 4px solid #007bff; background: #f8f9fa; }
        .user-info { background: #e7f3ff; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
        .step { background: #d4edda; padding: 0.75rem; margin: 0.5rem 0; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>\n";
    
    // Get some test users
    $users_sql = "SELECT id, username, email, created_at FROM users WHERE role = 'passenger' LIMIT 5";
    $users_stmt = $db->query($users_sql);
    $test_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($test_users)) {
        echo "<p>❌ No test users found. Please register some users first.</p>\n";
        exit;
    }
    
    echo "<div class='test-section'>\n";
    echo "<h3>👥 Available Test Users</h3>\n";
    echo "<p>You can use any of these users to test the password reset:</p>\n";
    
    echo "<table>\n";
    echo "<tr><th>Username</th><th>Email</th><th>Created</th><th>Test Instructions</th></tr>\n";
    
    foreach ($test_users as $user) {
        echo "<tr>\n";
        echo "<td><strong>{$user['username']}</strong></td>\n";
        echo "<td>{$user['email']}</td>\n";
        echo "<td>" . date('M j, Y', strtotime($user['created_at'])) . "</td>\n";
        echo "<td>Use email + username to reset password</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h3>🔄 How the Simple Password Reset Works</h3>\n";
    
    echo "<div class='step'>\n";
    echo "<h4>Step 1: Account Verification</h4>\n";
    echo "<ul>\n";
    echo "<li>✅ User enters their <strong>email</strong> and <strong>username</strong></li>\n";
    echo "<li>✅ System checks if both match an existing account</li>\n";
    echo "<li>✅ No email sending required - simple database verification</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div class='step'>\n";
    echo "<h4>Step 2: Password Reset</h4>\n";
    echo "<ul>\n";
    echo "<li>✅ User enters new password (minimum 6 characters)</li>\n";
    echo "<li>✅ User confirms the new password</li>\n";
    echo "<li>✅ Password is hashed and updated in database</li>\n";
    echo "<li>✅ User can immediately login with new password</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h3>🎯 Testing Instructions</h3>\n";
    echo "<div class='user-info'>\n";
    echo "<h4>To test the password reset:</h4>\n";
    echo "<ol>\n";
    echo "<li><strong>Go to:</strong> <a href='../public/forgot_password.php' target='_blank'>Password Reset Page</a></li>\n";
    echo "<li><strong>Step 1:</strong> Enter any email and username from the table above</li>\n";
    echo "<li><strong>Step 2:</strong> Enter a new password (min 6 characters)</li>\n";
    echo "<li><strong>Test:</strong> Try logging in with the new password</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h3>✅ Benefits of This Simple Approach</h3>\n";
    echo "<ul>\n";
    echo "<li>🚀 <strong>No Email Setup Required</strong> - Works immediately without SMTP configuration</li>\n";
    echo "<li>🔒 <strong>Still Secure</strong> - Requires both email AND username to verify identity</li>\n";
    echo "<li>👤 <strong>User Friendly</strong> - Clear 2-step process with visual feedback</li>\n";
    echo "<li>⚡ <strong>Fast</strong> - Immediate password reset without waiting for emails</li>\n";
    echo "<li>🎓 <strong>Educational</strong> - Shows core concepts without complexity</li>\n";
    echo "<li>🛠️ <strong>Workable</strong> - Fully functional for coursework and learning</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div class='test-section'>\n";
    echo "<h3>🔐 Security Features</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Password hashing using PHP's <code>password_hash()</code></li>\n";
    echo "<li>✅ Input validation and sanitization</li>\n";
    echo "<li>✅ SQL injection prevention with prepared statements</li>\n";
    echo "<li>✅ Password confirmation matching</li>\n";
    echo "<li>✅ Minimum password length requirement</li>\n";
    echo "<li>✅ Clear error messages for user guidance</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    // Quick password hash demo
    echo "<div class='test-section'>\n";
    echo "<h3>🧪 Password Hashing Demo</h3>\n";
    $demo_password = 'newpassword123';
    $hashed = password_hash($demo_password, PASSWORD_DEFAULT);
    
    echo "<p><strong>Original Password:</strong> <code>{$demo_password}</code></p>\n";
    echo "<p><strong>Hashed Version:</strong> <code style='word-break: break-all;'>{$hashed}</code></p>\n";
    echo "<p><strong>Verification:</strong> " . (password_verify($demo_password, $hashed) ? '✅ Match' : '❌ No Match') . "</p>\n";
    echo "<p><small>💡 This shows how passwords are securely stored in the database.</small></p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
