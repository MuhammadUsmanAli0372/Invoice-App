<script setup>
    import axios from 'axios';
    import { onMounted, ref } from 'vue';
    import { useRouter } from 'vue-router';

    const router = useRouter(); 

    let form = ref({
        id: ''
    });
    let allcustomers = ref([]);
    let customer_id = ref([]);
    const showModal = ref(false);
    const hideModal = ref(true);
    let listProducts = ref([]);

    const props = defineProps({
        id: {
            type: String,
            default: ''
        }
    });

    onMounted(async () => {
        getInvoice();
        getAllCustomers();
        getProducts();
    });

    const getInvoice = async () => {
        let response = await axios.get(`/api/edit_invoice/${props.id}`);
        // console.log('response', response.data);
        form.value = response.data.invoice;
    }

    const getAllCustomers = async () => {
        let response = await axios.get('/api/customers');
        // console.log('response', response.data);
        allcustomers.value = response.data.customers;
    }

    const deleteinvoiceItem = (id, i) => {
        form.value.invoice_items.splice(i, 1);
        if (id != undefined) {
            axios.get('/api/delete_invoice_items/'+id);
        }
    }

    const addCart = (item) => {

        const itemcart = {
            product_id: item.id,
            item_code: item.item_code,
            description: item.description,
            unit_price: item.unit_price,
            quantity: item.quantity,
        }

        //listCart.value.push(itemcart);
        form.value.invoice_items.push(itemcart);

        closeModal();
    }

    const removeItem = (i) => {
        listCart.value.splice(i, 1);
    }

    const openModal = () => {
        showModal.value = !showModal.value; 
    }

    const closeModal = () => {
        showModal.value = !hideModal.value
    }

    const getProducts = async () => {
        let response = await axios.get('/api/products');
        // console.log('response', response.data.products);
        listProducts.value = response.data.products;
    }

    const subTotal = () => {
        let total = 0;
        if (form.value.invoice_items) {
            form.value.invoice_items.map((data) => {
                total = total + (data.quantity * data.unit_price);
            });
        }
        return total;
    }

    const total = () => {
        if (form.value.invoice_items) {
            return subTotal() - form.value.discount;
        }
    }

    const onEdit = () => {
        if (form.value.invoice_items.length >= 1) {
            let subtotal = 0;
            subtotal = subTotal();

            let Total = 0;
            Total = total();

            const formData = new FormData();
            formData.append('invoice_items', JSON.stringify(form.value.invoice_items));
            formData.append('customer_id', form.value.customer_id);
            formData.append('date', form.value.date);
            formData.append('due_date', form.value.due_date);
            formData.append('number', form.value.number);
            formData.append('reference', form.value.reference);
            formData.append('discount', form.value.discount);
            formData.append('subtotal', subtotal);
            formData.append('total', Total);
            formData.append('terms_and_conditions', form.value.terms_and_conditions);

            axios.post(`/api/update_invoice/${form.value.id}`, formData);
            form.value.invoice_items = [];
            router.push('/');
        }
    }
</script>
<template>
    <div class="container">
        <!--==================== EDIT INVOICE ====================-->
        <div class="invoices">
            
            <div class="card__header">
                <div>
                    <h2 class="invoice__title">Edit Invoice</h2>
                </div>
                <div>
                    
                </div>
            </div>

            <div class="card__content">
                <div class="card__content--header">
                    <div>
                        <p class="my-1">Customer</p>
                        <select name="" id="" class="input" v-model="form.customer_id">
                            <option disabled>Select Customer</option>
                            <option :value="customer.id" v-for="customer in allcustomers" :key="customer.id">
                                {{ customer.firstname }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <p class="my-1">Date</p> 
                        <input id="date" placeholder="dd-mm-yyyy" type="date" class="input" v-model="form.date"> <!---->
                        <p class="my-1">Due Date</p> 
                        <input id="due_date" type="date" class="input" v-model="form.due_date">
                    </div>
                    <div>
                        <p class="my-1">Numero</p> 
                        <input type="text" class="input" v-model="form.number"> 
                        <p class="my-1">Reference(Optional)</p> 
                        <input type="text" class="input" v-model="form.referennce">
                    </div>
                </div>
                <br><br>
                <div class="table">

                    <div class="table--heading2">
                        <p>Item Description</p>
                        <p>Unit Price</p>
                        <p>Qty</p>
                        <p>Total</p>
                        <p></p>
                    </div>
        
                    <!-- item 1 -->
                    <div class="table--items2" v-for="(itemcart, i) in form.invoice_items" :key="itemcart.id">
                        <p v-if="itemcart.product">
                            #{{ itemcart.product.item_code }} {{ itemcart.product.description }}
                        </p>
                        <p v-else>
                            #{{ itemcart.item_code }} {{ itemcart.description }}
                        </p>
                        <p>
                            <input type="text" class="input" v-model="itemcart.unit_price">
                        </p>
                        <p>
                            <input type="text" class="input" v-model="itemcart.quantity">
                        </p>
                        <p>
                            $ {{ (itemcart.quantity) * (itemcart.unit_price) }}
                        </p>
                        <p style="color: red; font-size: 24px;cursor: pointer;" @click="deleteinvoiceItem(itemcart.id, i)">
                            &times;
                        </p>
                    </div>
                    <div style="padding: 10px 30px !important;">
                        <button class="btn btn-sm btn__open--modal"  @click="openModal()">
                            Add New Line
                        </button>
                    </div>
                </div>

                <div class="table__footer">
                    <div class="document-footer" >
                        <p>Terms and Conditions</p>
                        <textarea cols="50" rows="7" class="textarea" v-model="form.terms_and_conditions"></textarea>
                    </div>
                    <div>
                        <div class="table__footer--subtotal">
                            <p>Sub Total</p>
                            <span>$ {{ subTotal()  }}</span>
                        </div>
                        <div class="table__footer--discount">
                            <p>Discount</p>
                            <input type="text" class="input" v-model="form.discount">
                        </div>
                        <div class="table__footer--total">
                            <p>Grand Total</p>
                            <span>$ {{ total() }}</span>
                        </div>
                    </div>
                </div>

            
            </div>
            <div class="card__header" style="margin-top: 40px;">
                <div>
                    
                </div>
                <div>
                    <a class="btn btn-secondary" @click="onEdit()">
                        Save
                    </a>
                </div>
            </div>
            
        </div>
        <!--==================== add modal items ====================-->
        <div class="modal main__modal" :class="{ show: showModal }">
            <div class="modal__content">
                <span class="modal__close btn__close--modal" @click="closeModal()">×</span>
                <h3 class="modal__title">Add Item</h3>
                <hr><br>
                <div class="modal__items">
                    <!-- <select class="input my-1">
                        <option value="None">None</option>
                        <option value="None">LBC Padala</option>
                    </select> -->
                    <ul style="list-style: none">
                        <li v-for="(item, i) in listProducts" :key="item.id" style="display: grid; grid-template-columns: 30px 350px 15px; align-items: center; padding-bottom: 5px;">
                            <p>{{ i+1 }}</p>
                            <a href="#">{{ item.item_code }} {{ item.description }}</a>
                            <button @click="addCart(item)" style="border: 1px solid #e0e0e0; width: 35px; height: 35px; cursor: pointer;">
                                +
                            </button>
                        </li>
                    </ul>
                </div>
                <br><hr>
                <div class="model__footer">
                    <button class="btn btn-light mr-2 btn__close--modal" @click="closeModal()">
                        Cancel
                    </button>
                    <button class="btn btn-light btn__close--modal ">Save</button>
                </div>
            </div>
        </div>

    </div>
</template>