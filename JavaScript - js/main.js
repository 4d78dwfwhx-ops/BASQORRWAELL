// بيانات السيارات
const carData = {
    toyota: {
        models: ['كامري', 'كورولا', 'لاند كروزر', 'RAV4', 'هايلكس'],
        engines: ['2.0L', '2.5L', '3.5L V6', '4.0L V6']
    },
    hyundai: {
        models: ['سوناتا', 'إلنترا', 'توسان', 'سانتا في', 'أكسنت'],
        engines: ['1.6L', '2.0L', '2.4L', '2.5L Turbo']
    },
    nissan: {
        models: ['ألتيما', 'سنترا', 'باترول', 'إكس تريل', 'ماكسيما'],
        engines: ['1.6L', '2.0L', '2.5L', '4.0L V6', '5.6L V8']
    },
    kia: {
        models: ['أوبتيما', 'سيراتو', 'سبورتيج', 'سورينتو', 'بيكانتو'],
        engines: ['1.6L', '2.0L', '2.4L', '3.3L V6']
    },
    ford: {
        models: ['فيوجن', 'إكسبلورر', 'إيدج', 'F-150', 'موستانج'],
        engines: ['2.0L EcoBoost', '2.3L EcoBoost', '3.5L V6', '5.0L V8']
    },
    chevrolet: {
        models: ['ماليبو', 'كروز', 'تاهو', 'سيلفرادو', 'كامارو'],
        engines: ['1.5L Turbo', '2.0L Turbo', '3.6L V6', '5.3L V8', '6.2L V8']
    },
    mercedes: {
        models: ['C-Class', 'E-Class', 'S-Class', 'GLC', 'GLE'],
        engines: ['2.0L Turbo', '3.0L V6', '4.0L V8', '5.5L V8']
    },
    bmw: {
        models: ['Series 3', 'Series 5', 'Series 7', 'X3', 'X5'],
        engines: ['2.0L Turbo', '3.0L Turbo', '4.4L V8']
    },
    audi: {
        models: ['A4', 'A6', 'A8', 'Q5', 'Q7'],
        engines: ['2.0L TFSI', '3.0L TFSI', '4.0L TFSI']
    },
    volkswagen: {
        models: ['جيتا', 'باسات', 'تيجوان', 'توارق', 'غولف'],
        engines: ['1.4L TSI', '2.0L TSI', '3.0L V6']
    }
};

// تحديث الموديلات عند اختيار الماركة
document.getElementById('brand-select').addEventListener('change', function() {
    const brand = this.value;
    const modelSelect = document.getElementById('model-select');
    const engineSelect = document.getElementById('engine-select');
    
    // إعادة تعيين القوائم
    modelSelect.innerHTML = '<option value="">اختر الموديل</option>';
    engineSelect.innerHTML = '<option value="">اختر المحرك</option>';
    engineSelect.disabled = true;
    
    if (brand && carData[brand]) {
        // إضافة الموديلات
        carData[brand].models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.toLowerCase().replace(/\s+/g, '-');
            option.textContent = model;
            modelSelect.appendChild(option);
        });
        modelSelect.disabled = false;
    } else {
        modelSelect.disabled = true;
    }
});

// تحديث المحركات عند اختيار الموديل
document.getElementById('model-select').addEventListener('change', function() {
    const brand = document.getElementById('brand-select').value;
    const engineSelect = document.getElementById('engine-select');
    
    // إعادة تعيين قائمة المحركات
    engineSelect.innerHTML = '<option value="">اختر المحرك</option>';
    
    if (brand && carData[brand]) {
        // إضافة المحركات
        carData[brand].engines.forEach(engine => {
            const option = document.createElement('option');
            option.value = engine.toLowerCase().replace(/[\s.]+/g, '-');
            option.textContent = engine;
            engineSelect.appendChild(option);
        });
        engineSelect.disabled = false;
    }
});

// إدارة سلة التسوق
let cart = [];

function addToCart(product) {
    cart.push(product);
    updateCartCount();
    showNotification('تمت إضافة المنتج إلى السلة بنجاح!');
}

function updateCartCount() {
    document.getElementById('cart-count').textContent = cart.length;
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 20px;
        background: #27ae60;
        color: white;
        padding: 15px 25px;
        border-radius: 5px;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// إضافة مستمعي الأحداث لأزرار "أضف للسلة"
document.querySelectorAll('.btn-add-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productCard = this.closest('.product-card');
        const productName = productCard.querySelector('h4').textContent;
        const productBrand = productCard.querySelector('.brand').textContent;
        const productPrice = productCard.querySelector('.new-price').textContent;
        
        const product = {
            name: productName,
            brand: productBrand,
            price: productPrice
        };
        
        addToCart(product);
    });
});

// البحث المتقدم
document.querySelector('.btn-search').addEventListener('click', function() {
    const searchTerm = document.querySelector('.alternative-search input').value;
    if (searchTerm.trim()) {
        window.location.href = `/search?q=${encodeURIComponent(searchTerm)}`;
    }
});

// تحميل ديناميكي للصفحات
document.addEventListener('DOMContentLoaded', function() {
    console.log('منصة باسكر جاهزة للعمل!');
});
