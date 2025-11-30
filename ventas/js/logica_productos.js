function openProductModal(id, nameBase, priceBase, catId, desc) {
    currentCatId = catId; // Guardar categoría actual
    
    const modal = document.getElementById('product-modal');
    document.getElementById('modal-product-name').textContent = nameBase;
    document.getElementById('modal-product-desc').textContent = desc || '';
    
    const sizeGroup = document.getElementById('group-size');
    sizeGroup.innerHTML = '<p>Cargando...</p>';
    sizeGroup.style.display = 'block';
    
    renderExtras();
    renderFlavors(); // Importante llamar esto

    // --- VISIBILIDAD ---
    const isTisana = nameBase.toLowerCase().includes('tisana');
    
    // Sabores
    document.getElementById('group-flavors').style.display = isTisana ? 'block' : 'none';
    
    // Leche (Ocultar si es Comida, Extra o Tisana)
    const showMilk = !(catId == 5 || catId == 6 || isTisana);
    document.getElementById('group-milk').style.display = showMilk ? 'block' : 'none';
    
    // Extras (Ocultar si es Tisana)
    document.getElementById('group-extras').style.display = isTisana ? 'none' : 'block';

    // Botón Añadir
    const addBtn = document.querySelector('.btn-add-to-cart');
    addBtn.textContent = "Añadir al Carrito";
    addBtn.onclick = addToCart;

    modal.style.display = 'flex';

    fetch(`phps/obtenerTamanios.php?name=${encodeURIComponent(nameBase)}&category=${catId}`)
        .then(r => r.json())
        .then(variants => {
            if(!variants || variants.length === 0 || variants.error) {
                sizeGroup.style.display = 'none';
                // Pasamos catId para guardarlo
                selectFinalProduct({ idProducto: id, nombre: nameBase, precioVenta: priceBase });
                return;
            }
            
            sizeGroup.style.display = 'block';
            sizeGroup.innerHTML = '<h4>Tamaño:</h4>';
            const suffixes = ['Chico', 'Grande', 'Pequeño', 'Mediano', 'Vaso', 'Estándar', 'CH', 'G', 'M', 'Gde'];
            
            variants.forEach(v => {
                const btn = document.createElement('button');
                let disp = 'Estándar';
                for(const s of suffixes) { if(v.nombre.includes(s)) { disp = s; break; } }
                
                btn.textContent = `${disp} ($${parseFloat(v.precioVenta).toFixed(2)})`;
                btn.className = 'mod-option';
                btn.onclick = (e) => {
                    e.target.parentElement.querySelectorAll('.mod-option').forEach(s=>s.classList.remove('active'));
                    e.target.classList.add('active');
                    selectFinalProduct(v);
                };
                sizeGroup.appendChild(btn);
            });

            const first = sizeGroup.querySelector('.mod-option');
            if(first) { first.classList.add('active'); selectFinalProduct(variants[0]); }
        })
        .catch(e => {
            console.error(e);
            sizeGroup.style.display = 'none';
            selectFinalProduct({ idProducto: id, nombre: nameBase, precioVenta: priceBase });
        });
}
function selectFinalProduct(data) {
            // 1. Inicializar el producto base
            currentProduct = {
                id: data.idProducto,
                name: data.nombre,
                basePrice: parseFloat(data.precioVenta),
                quantity: 1,
                selectedModifiers: {},
                discountPercentage: 0,
                isEditing: false
            };
            
            // 2. Resetear Inputs
            document.getElementById('modal-quantity-input').value = 1;
            document.getElementById('modal-discount-input').value = 0;
            
            // 3. Resetear y Asignar Leche por Defecto
            document.querySelectorAll('#group-milk .mod-option').forEach(b => b.classList.remove('active'));
            
            const milkGroup = document.getElementById('group-milk');
            if (milkGroup.style.display !== 'none') {
                const defMilk = document.querySelector('#group-milk [data-mod-value="Entera"]');
                if(defMilk) {
                    defMilk.classList.add('active');
                    currentProduct.selectedModifiers['MilkBase'] = { value: 'Entera', adjust: 0 };
                }
            }
            
            // 4. Resetear Extras Visualmente
            document.querySelectorAll('#extras-container .mod-option').forEach(b => b.classList.remove('active'));

            // 5. LÓGICA DE SABOR POR DEFECTO (TISANAS)
            // Primero limpiamos cualquier selección visual anterior en sabores
            document.querySelectorAll('#flavors-container .mod-option').forEach(b => b.classList.remove('active'));

            // Si el nombre incluye "tisana", seleccionamos el primero automáticamente
            if (data.nombre.toLowerCase().includes('tisana')) {
                const firstFlavor = document.querySelector('#flavors-container .mod-option');
                if (firstFlavor) {
                    firstFlavor.classList.add('active'); // Activar visualmente
                    
                    // Guardar en el objeto del producto
                    currentProduct.selectedModifiers['Flavor'] = { 
                        value: firstFlavor.dataset.modValue, 
                        adjust: 0 
                    };
                }
            }
            
            // 6. Calcular Totales
            updateProductPrice();
        }        
       function updateProductPrice() {
            if(!currentProduct) return;
            
            let totalMods = Object.values(currentProduct.selectedModifiers).reduce((acc, m) => acc + m.adjust, 0);
            let unitTotal = currentProduct.basePrice + totalMods;
            
            const discInput = document.getElementById('modal-discount-input');
            const discP = parseFloat(discInput.value) || 0;
            currentProduct.discountPercentage = discP; // Guardar porcentaje en el producto
            const lineTotalNoDisc = unitTotal * currentProduct.quantity;
            const discAmt = lineTotalNoDisc * (discP / 100);
            const finalTotal = lineTotalNoDisc - discAmt;

            document.getElementById('modal-base-price').textContent = `$${currentProduct.basePrice.toFixed(2)}`;
            document.getElementById('modal-modifier-adjust').textContent = `+$${totalMods.toFixed(2)}`;
            document.getElementById('modal-discount-amt').textContent = discAmt.toFixed(2);
            document.getElementById('modal-discount-applied').textContent = `-$${discAmt.toFixed(2)}`;
            document.getElementById('modal-final-price').textContent = `$${finalTotal.toFixed(2)}`;
        }
 function updateModifierInProduct(btn) {
            if(!currentProduct) return;
            const key = btn.dataset.modName;
            
            if(btn.classList.contains('active')) {
                currentProduct.selectedModifiers[key] = {
                    value: btn.dataset.modValue,
                    adjust: parseFloat(btn.dataset.priceAdjust)
                };
            } else {
                delete currentProduct.selectedModifiers[key];
            }
            updateProductPrice();
        }
        function updateModalQuantity(chg) {
            if(!currentProduct) return;
            let val = parseInt(document.getElementById('modal-quantity-input').value) || 1;
            if(chg !== 0) val += chg;
            if(val < 1) val = 1;
            document.getElementById('modal-quantity-input').value = val;
            currentProduct.quantity = val;
            updateProductPrice();
        }
               function renderExtras() {
            const container = document.getElementById('extras-container');
            container.innerHTML = '';
            
            if(typeof globalExtras === 'undefined' || globalExtras.length === 0) {
                document.getElementById('group-extras').style.display = 'none';
                return;
            }
            document.getElementById('group-extras').style.display = 'block';

            globalExtras.forEach(ex => {
                const btn = document.createElement('button');
                const p = parseFloat(ex.precioVenta);
                btn.className = 'mod-option';
                btn.textContent = `${ex.nombre} (+$${p})`;
                btn.dataset.modName = 'Extra_' + ex.idProducto;
                btn.dataset.modValue = ex.nombre;
                btn.dataset.priceAdjust = p;
                
                btn.onclick = function() {
                    // Lógica de exclusividad para "Leche" en Extras
                    const thisName = this.dataset.modValue.toLowerCase();
                    if (thisName.includes("leche")) {
                        const siblings = container.querySelectorAll('.mod-option');
                        siblings.forEach(sib => {
                            if (sib !== this && sib.classList.contains('active')) {
                                const sibName = sib.dataset.modValue.toLowerCase();
                                if (sibName.includes("leche")) {
                                    sib.classList.remove('active');
                                    // Limpiar del objeto
                                    const sibModName = sib.dataset.modName;
                                    if(currentProduct && currentProduct.selectedModifiers[sibModName]) {
                                        delete currentProduct.selectedModifiers[sibModName];
                                    }
                                }
                            }
                        });
                    }

                    this.classList.toggle('active');
                    updateModifierInProduct(this);
                };
                container.appendChild(btn);
            });
        }
       function renderFlavors() {
            const container = document.getElementById('flavors-container');
            container.innerHTML = '';
            // Si no hay variable global de sabores (definida en PHP), salir
            if (typeof globalFlavors === 'undefined' || globalFlavors.length === 0) return;

            globalFlavors.forEach(flavor => {
                const btn = document.createElement('button');
                btn.className = 'mod-option';
                btn.textContent = flavor.nombre;
                btn.dataset.modName = 'Flavor';
                btn.dataset.modValue = flavor.nombre;
                btn.dataset.priceAdjust = 0;
                
                btn.onclick = function() {
                    container.querySelectorAll('.mod-option').forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    if(currentProduct) {
                        currentProduct.selectedModifiers['Flavor'] = { value: this.dataset.modValue, adjust: 0 };
                    }
                };
                container.appendChild(btn);
            });
        }
 function closeProductModal() {
            document.getElementById('product-modal').style.display = 'none';
            currentProduct = null;
        }