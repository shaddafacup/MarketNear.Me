    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-store"></i> MarketNearMe</h5>
                    <p>Your trusted local marketplace. Find everything you need right in your neighborhood.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/marketnearme/search.php" class="text-light">Browse Listings</a></li>
                        <li><a href="/marketnearme/post-ad.php" class="text-light">Post Free Ad</a></li>
                        <li><a href="/marketnearme/about.php" class="text-light">About Us</a></li>
                        <li><a href="/marketnearme/contact.php" class="text-light">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Categories</h5>
                    <ul class="list-unstyled">
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <li>
                            <a href="/marketnearme/search.php?category=<?php echo $category['id']; ?>" class="text-light">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> MarketNearMe. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/marketnearme/assets/js/main.js"></script>
    <!-- Scroll to Top Button -->
    <button id="scroll-top-btn" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>
</body>
</html>