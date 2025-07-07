    <!--
    HTML5 SEMANTIC ELEMENT: <footer>
    PURPOSE: Contains footer information and site credits
    STUDENT LEARNING: HTML5 semantic structure and proper page closure
    -->
    <footer class="site-footer" role="contentinfo">
        <div class="footer-content"
            <!--
            COPYRIGHT SECTION
            PURPOSE: Display copyright and project information
            STUDENT LEARNING: Professional footer structure
            -->
            <div class="footer-section">
                <p class="copyright">
                    &copy; 2025 Transport Booking System
                </p>
                <p class="project-info">
                    TWT6223 Web Techniques and Applications Project
                </p>
            </div>

            <!--
            CONTACT SECTION
            PURPOSE: Provide contact information
            STUDENT LEARNING: Complete website structure
            -->
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p><i class="fa fa-envelope" aria-hidden="true"></i> support@twt-transport.com</p>
                <p><i class="fa fa-phone" aria-hidden="true"></i> +60 3-1234 5678</p>
                <p><i class="fa fa-clock" aria-hidden="true"></i> 24/7 Customer Support</p>
            </div>
        </div>

        <!--
        FOOTER BOTTOM
        PURPOSE: Final credits and technical information
        STUDENT LEARNING: Professional footer completion
        -->
        <div class="footer-bottom">
            <p>Built with HTML5, CSS3, JavaScript, PHP & MySQL</p>
        </div>
    </footer>

    <!--
    JAVASCRIPT SECTION
    PURPOSE: Add interactive functionality
    STUDENT LEARNING: JavaScript integration and DOM manipulation
    -->
    <script>
        /*
        JAVASCRIPT: Basic interactivity for better user experience
        PURPOSE: Demonstrate JavaScript concepts learned in class
        STUDENT LEARNING: DOM manipulation, event handling, basic animations
        */

        // Wait for page to load completely
        document.addEventListener('DOMContentLoaded', function() {

            // Add smooth scrolling to all anchor links
            const links = document.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });

            // Add loading animation to forms (basic JavaScript)
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                        submitBtn.disabled = true;
                    }
                });
            });

            // Simple notification system for user feedback
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                notification.innerHTML = `
                    <i class="fa fa-info-circle"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.remove()">&times;</button>
                `;
                document.body.appendChild(notification);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);
            }

            // Make showNotification globally available
            window.showNotification = showNotification;
        });
    </script>
</body>
</html>

<!--
STUDENT NOTES:
1. HTML5 semantic footer structure improves accessibility
2. JavaScript DOM manipulation for interactivity
3. Event listeners demonstrate modern JavaScript concepts
4. Proper page structure closure with </body> and </html>
5. Comments explain each section for documentation purposes
-->
