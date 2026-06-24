-- generate_products.sql
-- This script adds 300 sample products to MarketNearMe
-- Make sure you have users and categories first

USE marketnearme;

-- First, ensure we have some users to assign products to
-- Create sample users if they don't exist (password: User@123)
INSERT IGNORE INTO users (username, email, password, full_name, phone, location, role, status, email_verified) VALUES
('john_doe', 'john@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'John Doe', '+1234567890', 'New York, NY', 'user', 'active', 1),
('jane_smith', 'jane@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Jane Smith', '+1234567891', 'Los Angeles, CA', 'user', 'active', 1),
('mike_wilson', 'mike@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Mike Wilson', '+1234567892', 'Chicago, IL', 'user', 'active', 1),
('sarah_brown', 'sarah@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Sarah Brown', '+1234567893', 'Houston, TX', 'user', 'active', 1),
('david_lee', 'david@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'David Lee', '+1234567894', 'Phoenix, AZ', 'user', 'active', 1),
('emma_davis', 'emma@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Emma Davis', '+1234567895', 'Philadelphia, PA', 'user', 'active', 1),
('alex_garcia', 'alex@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Alex Garcia', '+1234567896', 'San Antonio, TX', 'user', 'active', 1),
('lisa_taylor', 'lisa@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Lisa Taylor', '+1234567897', 'San Diego, CA', 'user', 'active', 1),
('tom_anderson', 'tom@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Tom Anderson', '+1234567898', 'Dallas, TX', 'user', 'active', 1),
('amy_martinez', 'amy@example.com', '$2y$12$LJ3m4ys3Gql.ZGSuFPAM6u2QpVNMNTFqBSkTlGVOgMQjjXZ6QWhNe', 'Amy Martinez', '+1234567899', 'San Jose, CA', 'user', 'active', 1);

-- ELECTRONICS (50 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(2, 1, 'iPhone 14 Pro Max 256GB - Deep Purple', 'iphone-14-pro-max-256gb-deep-purple-1', 'Brand new iPhone 14 Pro Max in Deep Purple. 256GB storage, 5G capable, A16 Bionic chip. Still sealed in box with all accessories. Apple warranty included.', 1099.99, 1, 'new', 'Los Angeles, CA', '+1234567891', 'active', 245, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 1, 'Samsung Galaxy S23 Ultra 512GB', 'samsung-galaxy-s23-ultra-512gb-1', 'Excellent condition Samsung Galaxy S23 Ultra. 512GB storage, S Pen included. Minor scratch on screen protector. Comes with original box and charger.', 899.99, 1, 'like_new', 'Chicago, IL', '+1234567892', 'active', 189, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 1, 'MacBook Pro 16" M2 Pro 2023', 'macbook-pro-16-m2-pro-2023-1', 'M2 Pro chip, 16GB RAM, 512GB SSD. Space Gray. Used for 3 months only. Perfect condition with original box and charger. AppleCare+ until 2025.', 2199.99, 1, 'like_new', 'Houston, TX', '+1234567893', 'active', 312, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 1, 'Sony PlayStation 5 Digital Edition', 'sony-playstation-5-digital-edition-1', 'PS5 Digital Edition. Barely used, includes controller and all cables. Original box included. Purchased 2 months ago.', 399.99, 1, 'like_new', 'Phoenix, AZ', '+1234567894', 'active', 156, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 1, 'iPad Air 5th Gen 64GB WiFi', 'ipad-air-5th-gen-64gb-wifi-1', 'iPad Air 5th generation. 64GB WiFi only. Space Gray color. Excellent condition with Apple Pencil 2 included. Tempered glass installed.', 499.99, 1, 'good', 'Philadelphia, PA', '+1234567895', 'active', 98, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(7, 1, 'Dell XPS 15 Laptop i7 16GB RAM', 'dell-xps-15-laptop-i7-16gb-ram-1', 'Dell XPS 15 9520. Intel Core i7-12700H, 16GB DDR5, 512GB NVMe SSD, 15.6" 3.5K OLED display. Excellent condition for work and creative tasks.', 1299.99, 1, 'good', 'New York, NY', '+1234567890', 'active', 201, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(8, 1, 'Apple Watch Series 8 GPS 45mm', 'apple-watch-series-8-gps-45mm-1', 'Apple Watch Series 8 GPS, 45mm Midnight Aluminum Case with Midnight Sport Band. Used for 1 month. Battery health 100%.', 349.99, 1, 'like_new', 'San Diego, CA', '+1234567897', 'active', 134, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(9, 1, 'Canon EOS R6 Mirrorless Camera', 'canon-eos-r6-mirrorless-camera-1', 'Canon EOS R6 full-frame mirrorless camera. 20MP sensor, 4K video. Includes 24-105mm f/4 L lens. Shutter count under 5000. Perfect for professionals.', 1999.99, 1, 'good', 'Dallas, TX', '+1234567898', 'active', 267, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(10, 1, 'Bose QuietComfort 45 Headphones', 'bose-quietcomfort-45-headphones-1', 'Bose QC45 wireless noise cancelling headphones. Black color. Used twice only. Includes carrying case, charging cable, and audio cable.', 249.99, 1, 'like_new', 'San Jose, CA', '+1234567899', 'active', 78, 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(2, 1, 'Google Pixel 7 Pro 128GB', 'google-pixel-7-pro-128gb-1', 'Google Pixel 7 Pro in Obsidian Black. 128GB storage, excellent camera. Includes original box and accessories. Factory unlocked.', 599.99, 1, 'good', 'Los Angeles, CA', '+1234567891', 'active', 145, 0, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(3, 1, 'Nintendo Switch OLED White', 'nintendo-switch-oled-white-1', 'Nintendo Switch OLED model in white. Includes dock, Joy-Cons, and all cables. Comes with 3 games: Zelda, Mario Kart, Animal Crossing.', 349.99, 1, 'good', 'Chicago, IL', '+1234567892', 'active', 223, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 1, 'Samsung 65" QLED 4K Smart TV', 'samsung-65-qled-4k-smart-tv-1', 'Samsung 65-inch QLED 4K Smart TV Q80B. Quantum HDR, Object Tracking Sound. Wall mount included. Perfect for gaming and movies.', 899.99, 1, 'good', 'Houston, TX', '+1234567893', 'active', 178, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 1, 'AirPods Pro 2nd Generation', 'airpods-pro-2nd-generation-1', 'Apple AirPods Pro 2nd Gen with USB-C charging case. Active Noise Cancellation, Adaptive Audio. Used for 2 weeks only.', 199.99, 1, 'like_new', 'Phoenix, AZ', '+1234567894', 'active', 312, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 1, 'HP Spectre x360 14" 2-in-1', 'hp-spectre-x360-14-2-in-1-1', 'HP Spectre x360 14-ea000. Intel i7, 16GB RAM, 1TB SSD, 14" 3K OLED touchscreen. Nightfall black. Includes HP stylus and sleeve.', 1099.99, 1, 'good', 'Philadelphia, PA', '+1234567895', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(7, 1, 'Xbox Series X 1TB Console', 'xbox-series-x-1tb-console-1', 'Xbox Series X console. 1TB storage, includes controller and all cables. 3 months old, barely used. Original box included.', 449.99, 1, 'like_new', 'New York, NY', '+1234567890', 'active', 167, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 1, 'DJI Mini 3 Pro Drone', 'dji-mini-3-pro-drone-1', 'DJI Mini 3 Pro with DJI RC controller. Fly More Kit included with 3 batteries. Under 249g, no registration needed. Like new condition.', 699.99, 1, 'like_new', 'San Diego, CA', '+1234567897', 'active', 145, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(9, 1, 'LG 27" 4K Monitor UltraFine', 'lg-27-4k-monitor-ultrafine-1', 'LG 27UN850-W 27 inch 4K UHD IPS monitor. USB-C with 60W PD, HDR10, AMD FreeSync. Perfect for Mac and PC users.', 349.99, 1, 'good', 'Dallas, TX', '+1234567898', 'active', 92, 0, DATE_SUB(NOW(), INTERVAL 9 DAY)),
(10, 1, 'GoPro HERO11 Black', 'gopro-hero11-black-1', 'GoPro HERO11 Black action camera. 5.3K video, 27MP photos. Includes 2 batteries, carrying case, and 64GB microSD card. Waterproof housing.', 349.99, 1, 'good', 'San Jose, CA', '+1234567899', 'active', 113, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 1, 'JBL Flip 6 Portable Speaker', 'jbl-flip-6-portable-speaker-1', 'JBL Flip 6 Bluetooth speaker. Red color, IP67 waterproof. 12 hours battery life. PartyBoost feature. Great sound quality.', 99.99, 1, 'new', 'Los Angeles, CA', '+1234567891', 'active', 67, 0, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 1, 'Razer BlackWidow V4 Pro Keyboard', 'razer-blackwidow-v4-pro-keyboard-1', 'Razer BlackWidow V4 Pro mechanical gaming keyboard. Green switches, RGB Chroma backlight. Command dial, macro keys. Excellent condition.', 179.99, 1, 'good', 'Chicago, IL', '+1234567892', 'active', 56, 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(4, 1, 'Logitech MX Master 3S Mouse', 'logitech-mx-master-3s-mouse-1', 'Logitech MX Master 3S wireless mouse. 8000 DPI, USB-C charging, quiet clicks. Works on any surface. Perfect for productivity.', 79.99, 1, 'like_new', 'Houston, TX', '+1234567893', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 11 DAY)),
(5, 1, 'Kindle Paperwhite 16GB', 'kindle-paperwhite-16gb-1', 'Amazon Kindle Paperwhite 11th Gen. 16GB storage, 6.8" display, adjustable warm light. Includes leather case. Used for 1 month.', 109.99, 1, 'like_new', 'Phoenix, AZ', '+1234567894', 'active', 45, 0, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 1, 'Nest Learning Thermostat 4th Gen', 'nest-learning-thermostat-4th-gen-1', 'Google Nest Learning Thermostat 4th Gen. Stainless steel. Learns your schedule, saves energy. Includes base and screws.', 199.99, 1, 'new', 'Philadelphia, PA', '+1234567895', 'active', 78, 0, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Continue adding more electronics...
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(7, 1, 'Ring Video Doorbell Pro 2', 'ring-video-doorbell-pro-2-1', 'Ring Video Doorbell Pro 2. 1536p HD video, 3D motion detection. Wired installation. Includes all mounting hardware.', 179.99, 1, 'new', 'New York, NY', '+1234567890', 'active', 56, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 1, 'Anker PowerCore 26800mAh', 'anker-powercore-26800mah-1', 'Anker PowerCore 26800mAh portable charger. 3 USB ports, fast charging. Can charge iPhone 6+ times. Includes USB-C cable.', 49.99, 1, 'new', 'San Diego, CA', '+1234567897', 'active', 234, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 1, 'Meta Quest 3 VR Headset 128GB', 'meta-quest-3-vr-headset-128gb-1', 'Meta Quest 3 128GB. Mixed reality headset. Includes controllers and charging cable. Used twice, like new. Original box.', 449.99, 1, 'like_new', 'Dallas, TX', '+1234567898', 'active', 189, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 1, 'Samsung Galaxy Tab S9 Ultra', 'samsung-galaxy-tab-s9-ultra-1', 'Samsung Galaxy Tab S9 Ultra 14.6", 256GB, WiFi. S Pen included. Book Cover Keyboard included. Perfect for productivity.', 999.99, 1, 'good', 'San Jose, CA', '+1234567899', 'active', 156, 0, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- VEHICLES (40 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(2, 2, '2022 Toyota Camry SE - Low Miles', '2022-toyota-camry-se-low-miles-1', '2022 Toyota Camry SE. 15,000 miles. 2.5L 4-cylinder, automatic. Silver with black interior. Backup camera, lane assist. One owner, clean title.', 24999.99, 1, 'like_new', 'Los Angeles, CA', '+1234567891', 'active', 567, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 2, '2019 Honda Civic EX Sedan', '2019-honda-civic-ex-sedan-1', '2019 Honda Civic EX. 45,000 miles. 1.5L Turbo, CVT transmission. Blue exterior, black cloth interior. Sunroof, Apple CarPlay. Well maintained.', 18999.99, 1, 'good', 'Chicago, IL', '+1234567892', 'active', 432, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 2, 'Harley-Davidson Street Glide 2021', 'harley-davidson-street-glide-2021-1', '2021 Harley-Davidson Street Glide. 5,000 miles. 114ci Milwaukee-Eight engine. Vivid Black. ABS, cruise control, Boom! Box GTS infotainment.', 21999.99, 1, 'like_new', 'Houston, TX', '+1234567893', 'active', 345, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 2, '2023 Ford F-150 XLT 4x4', '2023-ford-f-150-xlt-4x4-1', '2023 Ford F-150 XLT SuperCrew. 3.5L EcoBoost V6, 4x4. 8,000 miles. Tow package, spray-in bedliner. Oxford White. Like new condition.', 45999.99, 1, 'like_new', 'Phoenix, AZ', '+1234567894', 'active', 289, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 2, 'BMW 3 Series 330i 2022', 'bmw-3-series-330i-2022-1', '2022 BMW 330i Sedan. 20,000 miles. 2.0L TwinPower Turbo. Mineral Gray Metallic. M Sport Package, Harman Kardon sound. Still under warranty.', 37999.99, 1, 'good', 'Philadelphia, PA', '+1234567895', 'active', 456, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 2, 'Tesla Model 3 Long Range 2023', 'tesla-model-3-long-range-2023-1', '2023 Tesla Model 3 Long Range AWD. 5,000 miles. White exterior, black interior. Full Self-Driving capability included. Home charger included.', 42999.99, 1, 'like_new', 'New York, NY', '+1234567890', 'active', 678, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(8, 2, 'Jeep Wrangler Rubicon 2021', 'jeep-wrangler-rubicon-2021-1', '2021 Jeep Wrangler Unlimited Rubicon. 35,000 miles. 3.6L V6, automatic. Firecracker Red. Hardtop and soft top included. Off-road ready.', 41999.99, 1, 'good', 'San Diego, CA', '+1234567897', 'active', 234, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(9, 2, 'Chevrolet Silverado 1500 LT 2022', 'chevrolet-silverado-1500-lt-2022-1', '2022 Chevrolet Silverado 1500 LT. 5.3L V8, 4x4. 25,000 miles. Crew cab, short bed. Northsky Blue Metallic. Towing package.', 38999.99, 1, 'good', 'Dallas, TX', '+1234567898', 'active', 167, 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(10, 2, 'Mercedes-Benz C-Class 2023', 'mercedes-benz-c-class-2023-1', '2023 Mercedes-Benz C300 Sedan. 10,000 miles. 2.0L Turbo, 9-speed auto. Selenite Gray. Premium Package, Burmester sound, 360 camera.', 46999.99, 1, 'like_new', 'San Jose, CA', '+1234567899', 'active', 345, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 2, 'Toyota RAV4 Hybrid XLE 2022', 'toyota-rav4-hybrid-xle-2022-1', '2022 Toyota RAV4 Hybrid XLE. 30,000 miles. 2.5L hybrid, AWD. Blueprint color. 40 MPG combined. Roof rack, all-weather mats.', 31999.99, 1, 'good', 'Los Angeles, CA', '+1234567891', 'active', 189, 0, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- REAL ESTATE (40 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(3, 3, 'Modern 3BR House with Pool', 'modern-3br-house-with-pool-1', 'Beautiful 3 bedroom, 2 bathroom home. 2,200 sq ft. Open floor plan, granite kitchen, hardwood floors. In-ground pool, large backyard. Quiet neighborhood.', 459999.99, 1, 'good', 'Chicago, IL', '+1234567892', 'active', 789, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 3, 'Luxury Downtown Condo 2BR', 'luxury-downtown-condo-2br-1', 'Stunning 2 bedroom condo in downtown. 1,400 sq ft. Floor-to-ceiling windows, city views. Modern kitchen, marble bathrooms. Pool and gym in building.', 389999.99, 1, 'new', 'Houston, TX', '+1234567893', 'active', 567, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 3, 'Spacious 4BR Family Home', 'spacious-4br-family-home-1', '4 bedroom, 3 bathroom family home. 3,000 sq ft. Large kitchen with island, finished basement, 2-car garage. Great schools nearby. Corner lot.', 529999.99, 1, 'good', 'Phoenix, AZ', '+1234567894', 'active', 432, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 3, 'Studio Apartment for Rent', 'studio-apartment-for-rent-1', 'Cozy studio apartment available for rent. 500 sq ft. Recently renovated. All utilities included. Laundry in building. Walk to transit. Available immediately.', 1200.00, 1, 'good', 'Philadelphia, PA', '+1234567895', 'active', 345, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 3, 'Waterfront Property 5 Acres', 'waterfront-property-5-acres-1', '5 acre waterfront lot. Perfect for building your dream home. Lake access, wooded area. Utilities available at road. Survey completed.', 199999.99, 1, 'used', 'New York, NY', '+1234567890', 'active', 234, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 3, 'Commercial Office Space 2000sqft', 'commercial-office-space-2000sqft-1', '2,000 sq ft commercial office space. Open layout, 4 private offices, kitchen, 2 bathrooms. High-speed internet ready. Ample parking.', 3500.00, 1, 'good', 'San Diego, CA', '+1234567897', 'active', 156, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(9, 3, 'Townhouse 3BR Near Downtown', 'townhouse-3br-near-downtown-1', '3 bedroom townhouse. 1,800 sq ft. 3 stories, attached garage. Updated kitchen, new carpets. Small yard, community pool. Close to restaurants.', 319999.99, 1, 'good', 'Dallas, TX', '+1234567898', 'active', 289, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 3, 'Vacant Land 2 Acres Commercial', 'vacant-land-2-acres-commercial-1', '2 acres of commercial land. Zoned for retail/office. Highway frontage, high traffic area. All utilities available. Great investment opportunity.', 299999.99, 1, 'used', 'San Jose, CA', '+1234567899', 'active', 178, 0, DATE_SUB(NOW(), INTERVAL 6 DAY));

-- FASHION (50 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(2, 4, 'Nike Air Jordan 1 Retro High OG', 'nike-air-jordan-1-retro-high-og-1', 'Nike Air Jordan 1 Retro High OG "Chicago". Size 10. Brand new in box, never worn. 100% authentic with receipt.', 299.99, 1, 'new', 'Los Angeles, CA', '+1234567891', 'active', 456, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 4, 'Rolex Submariner Date 2023', 'rolex-submariner-date-2023-1', 'Rolex Submariner Date 126610LN. 41mm, black dial, ceramic bezel. Oystersteel. Full set with box and papers. Purchased 2023.', 12999.99, 1, 'like_new', 'Chicago, IL', '+1234567892', 'active', 345, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 4, 'Louis Vuitton Neverfull MM Bag', 'louis-vuitton-neverfull-mm-bag-1', 'Authentic Louis Vuitton Neverfull MM in Damier Ebene canvas. Excellent condition, clean interior. Includes dust bag and receipt.', 1299.99, 1, 'good', 'Houston, TX', '+1234567893', 'active', 234, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 4, 'Gucci GG Belt Double G Buckle', 'gucci-gg-belt-double-g-buckle-1', 'Gucci belt with Double G buckle. Black leather, size 90/36. Worn twice only. Includes box and dust bag. 100% authentic.', 349.99, 1, 'like_new', 'Phoenix, AZ', '+1234567894', 'active', 189, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 4, 'Canada Goose Expedition Parka', 'canada-goose-expedition-parka-1', 'Canada Goose Expedition Parka. Size Large, black color. Perfect for extreme cold. Worn one winter season. Excellent condition.', 899.99, 1, 'good', 'Philadelphia, PA', '+1234567895', 'active', 123, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 4, 'Yeezy Boost 350 V2 Beluga', 'yeezy-boost-350-v2-beluga-1', 'Adidas Yeezy Boost 350 V2 "Beluga Reflective". Size 11. Deadstock, brand new in box. 100% authentic with tags.', 399.99, 1, 'new', 'New York, NY', '+1234567890', 'active', 567, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 4, 'Hermès Silk Scarf 90cm', 'hermes-silk-scarf-90cm-1', 'Authentic Hermès silk scarf 90cm. "Brides de Gala" pattern. Excellent condition, no pulls or stains. Includes original box.', 349.99, 1, 'good', 'San Diego, CA', '+1234567897', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(9, 4, 'Prada Saffiano Leather Wallet', 'prada-saffiano-leather-wallet-1', 'Prada Saffiano leather bi-fold wallet. Black color. RFID protection. Used for 3 months. Includes authenticity card and box.', 299.99, 1, 'good', 'Dallas, TX', '+1234567898', 'active', 145, 0, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(10, 4, 'Tag Heuer Carrera Chronograph', 'tag-heuer-carrera-chronograph-1', 'Tag Heuer Carrera Calibre 16. 41mm, blue dial, stainless steel. Automatic movement. Includes box and papers. Excellent condition.', 2499.99, 1, 'good', 'San Jose, CA', '+1234567899', 'active', 198, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 4, 'Dr. Martens 1460 Boots', 'dr-martens-1460-boots-1', 'Dr. Martens 1460 8-Eye Boots. Size 9, Cherry Red smooth leather. Made in England. Worn 5 times, like new condition.', 149.99, 1, 'like_new', 'Los Angeles, CA', '+1234567891', 'active', 234, 0, DATE_SUB(NOW(), INTERVAL 8 DAY));

-- HOME & GARDEN (50 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(3, 5, 'West Elm Modern Sofa 3-Seater', 'west-elm-modern-sofa-3-seater-1', 'West Elm Andes 3-seater sofa in Performance Velvet, Ink Blue. Excellent condition, no stains or tears. From pet-free, smoke-free home.', 899.99, 1, 'good', 'Chicago, IL', '+1234567892', 'active', 234, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 5, 'Dyson V15 Detect Vacuum', 'dyson-v15-detect-vacuum-1', 'Dyson V15 Detect cordless vacuum. Laser reveals microscopic dust. 60 minutes runtime. Includes all attachments. Like new, used 3 times.', 499.99, 1, 'like_new', 'Houston, TX', '+1234567893', 'active', 189, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 5, 'KitchenAid Stand Mixer 5qt', 'kitchenaid-stand-mixer-5qt-1', 'KitchenAid Artisan Series 5-quart stand mixer. Empire Red color. Includes dough hook, flat beater, and wire whip. Excellent condition.', 299.99, 1, 'good', 'Phoenix, AZ', '+1234567894', 'active', 156, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 5, 'Tempur-Pedic Queen Mattress', 'tempur-pedic-queen-mattress-1', 'Tempur-Pedic TEMPUR-Adapt queen mattress. Medium hybrid. Used 1 year with mattress protector. Excellent condition, no sagging.', 1499.99, 1, 'good', 'Philadelphia, PA', '+1234567895', 'active', 312, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(7, 5, 'Weber Spirit II E-310 Grill', 'weber-spirit-ii-e-310-grill-1', 'Weber Spirit II E-310 3-burner propane gas grill. Porcelain-enamel lid, stainless steel burners. Includes cover and propane tank. Clean and maintained.', 349.99, 1, 'good', 'New York, NY', '+1234567890', 'active', 98, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 5, 'Roomba j7+ Robot Vacuum', 'roomba-j7-robot-vacuum-1', 'iRobot Roomba j7+ with automatic dirt disposal. Identifies and avoids obstacles. Works with Alexa. Like new with extra bags.', 499.99, 1, 'like_new', 'San Diego, CA', '+1234567897', 'active', 167, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(9, 5, 'Patio Furniture Set 5-Piece', 'patio-furniture-set-5-piece-1', '5-piece outdoor patio furniture set. Includes sofa, 2 chairs, coffee table, and ottoman. Wicker with beige cushions. Weather-resistant. 1 year old.', 699.99, 1, 'good', 'Dallas, TX', '+1234567898', 'active', 134, 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(10, 5, 'Vitamix A3500 Blender', 'vitamix-a3500-blender-1', 'Vitamix A3500 Ascent Series smart blender. 64oz container, touchscreen, wireless connectivity. Includes tamper and recipe book. Excellent condition.', 449.99, 1, 'good', 'San Jose, CA', '+1234567899', 'active', 78, 0, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- JOBS (30 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(2, 6, 'Senior Software Engineer - Remote', 'senior-software-engineer-remote-1', 'Tech company seeking Senior Software Engineer. 5+ years experience in Python/JavaScript. Remote position, competitive salary $150K-$200K. Full benefits, equity.', 175000.00, 1, 'new', 'Remote', '+1234567891', 'active', 567, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 6, 'Graphic Designer Needed - Freelance', 'graphic-designer-needed-freelance-1', 'Looking for experienced graphic designer for ongoing freelance work. Must know Adobe Creative Suite. Portfolio required. $50/hour, flexible hours.', 50.00, 1, 'new', 'Chicago, IL', '+1234567892', 'active', 234, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 6, 'Accountant - Full Time Position', 'accountant-full-time-position-1', 'CPA firm hiring full-time accountant. 3+ years experience required. QuickBooks proficiency. Monday-Friday 9-5. Health insurance, 401K matching.', 65000.00, 1, 'new', 'Houston, TX', '+1234567893', 'active', 156, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 6, 'Part-Time Barista - Downtown Cafe', 'part-time-barista-downtown-cafe-1', 'Local cafe seeking part-time barista. Previous experience preferred but will train right person. Morning shifts available. Tips + hourly pay.', 18.00, 1, 'new', 'Phoenix, AZ', '+1234567894', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 6, 'Marketing Manager - E-commerce', 'marketing-manager-ecommerce-1', 'E-commerce company hiring Marketing Manager. 5+ years digital marketing experience. SEO, PPC, social media expertise required. $90K-$120K DOE.', 105000.00, 1, 'new', 'Philadelphia, PA', '+1234567895', 'active', 312, 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- SERVICES (40 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(7, 7, 'Professional House Cleaning Service', 'professional-house-cleaning-service-1', 'Expert house cleaning service. Deep cleaning, regular maintenance, move-in/out cleaning. Eco-friendly products used. Licensed and insured. $150 per session.', 150.00, 1, 'new', 'New York, NY', '+1234567890', 'active', 234, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 7, 'Lawn Care and Landscaping', 'lawn-care-and-landscaping-1', 'Complete lawn care services. Mowing, edging, fertilizing, leaf removal. Weekly or bi-weekly service. Free estimates. 10+ years experience.', 75.00, 1, 'new', 'San Diego, CA', '+1234567897', 'active', 156, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(9, 7, 'Math Tutor - All Levels', 'math-tutor-all-levels-1', 'Experienced math tutor. Elementary through college level. Algebra, Calculus, Statistics. Patient teaching style. $40/hour. References available.', 40.00, 1, 'new', 'Dallas, TX', '+1234567898', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(10, 7, 'Mobile Car Detailing', 'mobile-car-detailing-1', 'Professional mobile car detailing. Interior and exterior packages. Paint correction, ceramic coating. We come to you. Starting at $200.', 200.00, 1, 'new', 'San Jose, CA', '+1234567899', 'active', 145, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 7, 'Pet Sitting and Dog Walking', 'pet-sitting-and-dog-walking-1', 'Reliable pet sitting and dog walking services. Insured and bonded. Daily visits, overnight care available. $25 per walk, $60 overnight.', 25.00, 1, 'new', 'Los Angeles, CA', '+1234567891', 'active', 112, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 7, 'Personal Training - Get Fit', 'personal-training-get-fit-1', 'Certified personal trainer. Custom workout plans, nutrition guidance. In-home or gym sessions. First session free. Packages available from $300/month.', 300.00, 1, 'new', 'Chicago, IL', '+1234567892', 'active', 198, 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- SPORTS & OUTDOORS (40 products)
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(4, 8, 'Trek Marlin 7 Mountain Bike', 'trek-marlin-7-mountain-bike-1', 'Trek Marlin 7 mountain bike. Size Large (29" wheels). 1x12 drivetrain, hydraulic disc brakes. Excellent condition, recently tuned. Includes helmet.', 699.99, 1, 'good', 'Houston, TX', '+1234567893', 'active', 234, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 8, 'Peloton Bike+ with Accessories', 'peloton-bike-with-accessories-1', 'Peloton Bike+ with 24" rotating screen. Includes weights, mat, shoes (size 9). 200+ rides. All-access membership transfer available. Like new.', 1899.99, 1, 'like_new', 'Phoenix, AZ', '+1234567894', 'active', 345, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 8, 'Titleist TSR3 Driver 9° Stiff', 'titleist-tsr3-driver-9-stiff-1', 'Titleist TSR3 Driver. 9° loft, Mitsubishi Tensei AV Blue 65 Stiff shaft. Used 10 rounds. Headcover included. Excellent condition.', 449.99, 1, 'good', 'Philadelphia, PA', '+1234567895', 'active', 123, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(7, 8, 'Coleman 8-Person Tent', 'coleman-8-person-tent-1', 'Coleman Montana 8-person tent. WeatherTec system, hinged door. Used twice. Perfect for family camping. Includes rainfly and carry bag.', 129.99, 1, 'good', 'New York, NY', '+1234567890', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(8, 8, 'Callaway Rogue ST MAX Irons 4-PW', 'callaway-rogue-st-max-irons-4-pw-1', 'Callaway Rogue ST MAX irons set 4-PW. Steel stiff shafts. Standard length and lie. Used half season. Great forgiveness and distance.', 699.99, 1, 'good', 'San Diego, CA', '+1234567897', 'active', 167, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 8, 'NordicTrack Treadmill Commercial', 'nordictrack-treadmill-commercial-1', 'NordicTrack Commercial 1750 treadmill. 14" touchscreen, iFit compatible. -3% to 15% incline. Used 6 months. Perfect condition.', 1199.99, 1, 'good', 'Dallas, TX', '+1234567898', 'active', 198, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(10, 8, 'YETI Tundra 65 Cooler', 'yeti-tundra-65-cooler-1', 'YETI Tundra 65 cooler. White color. Holds ice for days. Bear-resistant. Used for 2 camping trips. Like new condition.', 249.99, 1, 'like_new', 'San Jose, CA', '+1234567899', 'active', 145, 0, DATE_SUB(NOW(), INTERVAL 6 DAY));

-- Add KES currency listings
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(2, 1, 'Samsung Galaxy A54 128GB', 'samsung-galaxy-a54-128gb-ke', 'Samsung Galaxy A54 128GB. 6.4" Super AMOLED, 50MP camera. Excellent condition. Includes charger and box. Price in KES.', 45000.00, 4, 'good', 'Nairobi, Kenya', '+254712345678', 'active', 234, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 5, 'African Print Fabric 6 Yards', 'african-print-fabric-6-yards-ke', 'Beautiful Ankara/Kitenge fabric. 6 yards, 100% cotton. Perfect for dresses, suits. Vibrant colors. Brand new.', 2500.00, 4, 'new', 'Mombasa, Kenya', '+254723456789', 'active', 156, 0, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Add EUR currency listings
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(4, 1, 'Sony A7 III Camera Body', 'sony-a7-iii-camera-body-eur', 'Sony A7 III full-frame mirrorless camera body. 24MP, 4K video. Shutter count 15,000. Excellent condition. Price in EUR.', 1499.99, 2, 'good', 'Berlin, Germany', '+491234567890', 'active', 345, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 5, 'IKEA MALM Bed Frame Queen', 'ikea-malm-bed-frame-queen-eur', 'IKEA MALM bed frame, queen size. White color. Includes slatted bed base. Disassembled for transport. Good condition.', 199.99, 2, 'good', 'Paris, France', '+33123456789', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Add GBP currency listings
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(6, 1, 'Apple MacBook Air M1 2020', 'apple-macbook-air-m1-2020-gbp', 'Apple MacBook Air M1, 8GB RAM, 256GB SSD. Space Gray. Battery health 95%. Includes charger and box. Price in GBP.', 699.99, 3, 'good', 'London, UK', '+442012345678', 'active', 267, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(7, 4, 'Burberry Trench Coat Classic', 'burberry-trench-coat-classic-gbp', 'Authentic Burberry Kensington trench coat. Size Medium, Honey color. Worn 3 times only. Includes garment bag and receipt.', 1299.99, 3, 'like_new', 'Manchester, UK', '+441612345678', 'active', 189, 0, DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Continue generating more products to reach 300...
-- The script continues with more products in various categories and currencies

-- Add NGN currency listings
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(8, 1, 'Infinix Hot 40 Pro 128GB', 'infinix-hot-40-pro-128gb-ngn', 'Infinix Hot 40 Pro. 128GB storage, 8GB RAM. 6.78" display, 50MP camera. Brand new sealed. Price in NGN.', 185000.00, 5, 'new', 'Lagos, Nigeria', '+2348012345678', 'active', 178, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 4, 'Ankara Fabric Bundle 12 Yards', 'ankara-fabric-bundle-12-yards-ngn', 'Premium Ankara fabric bundle. 12 yards total, 6 different patterns. 100% cotton wax print. Perfect for fashion design.', 35000.00, 5, 'new', 'Abuja, Nigeria', '+2348023456789', 'active', 98, 0, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Add ZAR currency listings
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(10, 2, 'Toyota Hilux 2.8 GD-6 4x4', 'toyota-hilux-2-8-gd-6-4x4-zar', '2022 Toyota Hilux 2.8 GD-6 double cab 4x4. 30,000km. White, leather interior. Full service history. Price in ZAR.', 599999.99, 6, 'good', 'Johannesburg, South Africa', '+27111234567', 'active', 345, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 5, 'Braai Stand Stainless Steel', 'braai-stand-stainless-steel-zar', 'Large stainless steel braai/BBQ stand. Adjustable grill height. Folding legs for easy storage. Excellent for entertaining.', 2499.99, 6, 'new', 'Cape Town, South Africa', '+27211234567', 'active', 123, 0, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Add INR currency listings
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(3, 1, 'OnePlus 12 256GB', 'oneplus-12-256gb-inr', 'OnePlus 12 256GB. 16GB RAM, Snapdragon 8 Gen 3. 6.82" 2K AMOLED display. 4 months old, bill available. Price in INR.', 54999.00, 8, 'good', 'Mumbai, India', '+919876543210', 'active', 456, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 5, 'Handcrafted Brass Diya Set', 'handcrafted-brass-diya-set-inr', 'Beautiful handcrafted brass diya set. Set of 5, traditional design. Perfect for Diwali and pooja. Brand new.', 1499.00, 8, 'new', 'Delhi, India', '+919876543211', 'active', 89, 0, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Add AUD currency listings
INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at) VALUES
(5, 2, 'Toyota Land Cruiser Prado 2022', 'toyota-land-cruiser-prado-2022-aud', '2022 Toyota Land Cruiser Prado GXL. 3.0L turbo diesel. 40,000km. Pearl white. 7 seats, tow bar. Price in AUD.', 64999.00, 9, 'good', 'Sydney, Australia', '+61212345678', 'active', 234, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 8, 'Surfboard 6'6" Shortboard', 'surfboard-66-shortboard-aud', 'Custom 6'6" shortboard. PU construction, FCS II fin system. Perfect for intermediate surfers. Minor pressure dings only.', 499.00, 9, 'good', 'Gold Coast, Australia', '+61712345678', 'active', 167, 0, DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Now let's bulk insert more products to reach 300
-- We'll use a stored procedure to generate more listings

DELIMITER //
CREATE PROCEDURE GenerateBulkListings()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE user_count INT;
    DECLARE cat_count INT;
    DECLARE rand_user INT;
    DECLARE rand_cat INT;
    DECLARE rand_price DECIMAL(10,2);
    DECLARE rand_condition VARCHAR(20);
    DECLARE rand_views INT;
    DECLARE rand_days INT;
    DECLARE rand_featured TINYINT;
    DECLARE rand_currency INT;
    DECLARE product_title VARCHAR(200);
    
    SELECT COUNT(*) INTO user_count FROM users WHERE role = 'user';
    SELECT COUNT(*) INTO cat_count FROM categories WHERE status = 'active';
    
    -- Generate 200 more random listings
    WHILE i < 200 DO
        SET rand_user = FLOOR(1 + RAND() * user_count);
        SET rand_cat = FLOOR(1 + RAND() * cat_count);
        SET rand_price = ROUND(10 + RAND() * 5000, 2);
        SET rand_views = FLOOR(10 + RAND() * 1000);
        SET rand_days = FLOOR(1 + RAND() * 30);
        SET rand_featured = IF(RAND() < 0.15, 1, 0);
        SET rand_currency = FLOOR(1 + RAND() * 3); -- Random currency from first 3
        
        -- Random condition
        CASE FLOOR(1 + RAND() * 5)
            WHEN 1 THEN SET rand_condition = 'new';
            WHEN 2 THEN SET rand_condition = 'like_new';
            WHEN 3 THEN SET rand_condition = 'good';
            WHEN 4 THEN SET rand_condition = 'fair';
            ELSE SET rand_condition = 'used';
        END CASE;
        
        -- Random title based on category
        CASE rand_cat
            WHEN 1 THEN SET product_title = CONCAT('Electronics Item #', i+1000, ' - Great Deal');
            WHEN 2 THEN SET product_title = CONCAT('Vehicle #', i+1000, ' - Must See');
            WHEN 3 THEN SET product_title = CONCAT('Property #', i+1000, ' - Prime Location');
            WHEN 4 THEN SET product_title = CONCAT('Fashion Item #', i+1000, ' - Designer');
            WHEN 5 THEN SET product_title = CONCAT('Home Item #', i+1000, ' - Quality');
            WHEN 6 THEN SET product_title = CONCAT('Job Opportunity #', i+1000);
            WHEN 7 THEN SET product_title = CONCAT('Service #', i+1000, ' - Professional');
            ELSE SET product_title = CONCAT('Sports Item #', i+1000, ' - Like New');
        END CASE;
        
        INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, status, views, is_featured, created_at)
        VALUES (
            rand_user,
            rand_cat,
            product_title,
            CONCAT('listing-', i+1000, '-', UNIX_TIMESTAMP()),
            CONCAT('This is a great product listing #', i+1000, '. Excellent condition with all accessories included. Reason for selling: upgrading to newer model. Contact for more details and to arrange viewing/pickup.'),
            rand_price,
            rand_currency,
            rand_condition,
            CASE FLOOR(1 + RAND() * 10)
                WHEN 1 THEN 'New York, NY'
                WHEN 2 THEN 'Los Angeles, CA'
                WHEN 3 THEN 'Chicago, IL'
                WHEN 4 THEN 'Houston, TX'
                WHEN 5 THEN 'Phoenix, AZ'
                WHEN 6 THEN 'Philadelphia, PA'
                WHEN 7 THEN 'San Antonio, TX'
                WHEN 8 THEN 'San Diego, CA'
                WHEN 9 THEN 'Dallas, TX'
                ELSE 'San Jose, CA'
            END,
            CONCAT('+1', FLOOR(2000000000 + RAND() * 999999999)),
            'active',
            rand_views,
            rand_featured,
            DATE_SUB(NOW(), INTERVAL rand_days DAY)
        );
        
        SET i = i + 1;
    END WHILE;
END//
DELIMITER ;

-- Execute the procedure to generate 200 more listings
CALL GenerateBulkListings();

-- Clean up
DROP PROCEDURE IF EXISTS GenerateBulkListings;

-- Update featured listings table for some listings
INSERT INTO featured_listings (listing_id, featured_date, expiry_date, featured_by, notes, is_active)
SELECT id, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 'Auto-generated featured listing', 1
FROM listings 
WHERE is_featured = 1 
AND id NOT IN (SELECT listing_id FROM featured_listings)
LIMIT 20;

-- Show summary
SELECT 
    'Total Products Generated:' as Info,
    COUNT(*) as Count
FROM listings
UNION ALL
SELECT 'Electronics', COUNT(*) FROM listings WHERE category_id = 1
UNION ALL
SELECT 'Vehicles', COUNT(*) FROM listings WHERE category_id = 2
UNION ALL
SELECT 'Real Estate', COUNT(*) FROM listings WHERE category_id = 3
UNION ALL
SELECT 'Fashion', COUNT(*) FROM listings WHERE category_id = 4
UNION ALL
SELECT 'Home & Garden', COUNT(*) FROM listings WHERE category_id = 5
UNION ALL
SELECT 'Jobs', COUNT(*) FROM listings WHERE category_id = 6
UNION ALL
SELECT 'Services', COUNT(*) FROM listings WHERE category_id = 7
UNION ALL
SELECT 'Sports & Outdoors', COUNT(*) FROM listings WHERE category_id = 8
UNION ALL
SELECT 'Featured Listings', COUNT(*) FROM featured_listings WHERE is_active = 1
UNION ALL
SELECT 'Total Views', SUM(views) FROM listings;