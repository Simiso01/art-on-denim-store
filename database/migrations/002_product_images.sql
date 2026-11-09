-- Update product images to real photos (run if DB already imported)
USE `art_on_denim`;

DELETE FROM `product_images`;

INSERT INTO `product_images` (`product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`) VALUES
(1,  'assets/images/products/indigo-selvedge-jean.jpg', 'Indigo Raw Selvedge Jean', 1, 0),
(1,  'assets/images/products/indigo-selvedge-jean-2.jpg', 'Indigo Raw Selvedge Jean alternate view', 0, 1),
(2,  'assets/images/products/black-straight-jean.jpg', 'Washed Black Straight Jean', 1, 0),
(3,  'assets/images/products/canvas-utility-jacket.jpg', 'Canvas Utility Jacket', 1, 0),
(4,  'assets/images/products/drop-crotch-pant.jpg', 'Rick Owens Drop-Crotch Pant', 1, 0),
(5,  'assets/images/products/oversized-level-tee.jpg', 'Rick Owens Level Tee', 1, 0),
(6,  'assets/images/products/architectural-jacket.jpg', 'Rick Owens Bauhaus Jacket', 1, 0),
(6,  'assets/images/products/architectural-jacket-2.jpg', 'Rick Owens Bauhaus Jacket alternate view', 0, 1),
(7,  'assets/images/products/essentials-hoodie.jpg', 'Fear of God Essentials Hoodie', 1, 0),
(7,  'assets/images/products/essentials-hoodie-2.jpg', 'Fear of God Essentials Hoodie alternate view', 0, 1),
(8,  'assets/images/products/knit-cardigan.jpg', 'AMI De Coeur Cardigan', 1, 0),
(9,  'assets/images/products/soft-shell-outerwear.jpg', 'Stone Island Soft Shell', 1, 0),
(10, 'assets/images/products/structured-cap.jpg', 'AOD Brass Cap', 1, 0),
(11, 'assets/images/products/replica-sneaker.jpg', 'Maison Margiela Replica Sneaker', 1, 0),
(11, 'assets/images/products/replica-sneaker-2.jpg', 'Maison Margiela Replica Sneaker alternate view', 0, 1),
(12, 'assets/images/products/wide-crop-denim.jpg', 'AOD Wide Crop Denim', 1, 0);
