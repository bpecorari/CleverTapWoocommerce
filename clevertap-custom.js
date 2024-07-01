document.addEventListener("DOMContentLoaded", function() {
    // Capturar clique nos links de produtos na lista de produtos
    document.querySelectorAll('.products .product a').forEach(function(element) {
        element.addEventListener('click', function(event) {
            var productId = this.closest('.product').getAttribute('data-product_id');
            var productName = this.closest('.product').querySelector('.woocommerce-loop-product__title') ? this.closest('.product').querySelector('.woocommerce-loop-product__title').innerText : 'N/A';
            var productPrice = this.closest('.product').querySelector('.price') ? this.closest('.product').querySelector('.price').innerText : 'N/A';

            console.log("Clique no Produto Capturado");
            console.log("Product ID:", productId);
            console.log("Product Name:", productName);
            console.log("Product Price:", productPrice);
        });
    });
});
