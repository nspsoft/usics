<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    BuildingOffice2Icon, 
    GlobeAltIcon, 
    PhoneIcon, 
    EnvelopeIcon, 
    MapPinIcon,
    IdentificationIcon,
    PhotoIcon,
    CheckBadgeIcon,
    ClockIcon,
    BanknotesIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    company: Object,
    print_settings: Object,
    helpdesk_settings: Object
});

const logoPreview = ref(props.company?.logo || '/images/jicos.png');

const form = useForm({
    name: props.company?.name || '',
    legal_name: props.company?.legal_name || '',
    email: props.company?.email || '',
    phone: props.company?.phone || '',
    website: props.company?.website || '',
    tax_id: props.company?.tax_id || '',
    address: props.company?.address || '',
    city: props.company?.city || '',
    state: props.company?.state || '',
    postal_code: props.company?.postal_code || '',
    currency: props.company?.currency || 'IDR',
    timezone: props.company?.timezone || 'Asia/Jakarta',
    logo_file: null,
    company_logo_text: props.print_settings?.company_logo_text || 'jidoka',
    company_full_name: props.print_settings?.company_full_name || 'PT. JIDOKA RESULT INDONESIA',
    company_address: props.print_settings?.company_address || '',
    print_logo_file: null,
    helpdesk_wa_number: props.helpdesk_settings?.helpdesk_wa_number || '',
    helpdesk_email_address: props.helpdesk_settings?.helpdesk_email_address || '',
});

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo_file = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('settings.company.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            // Success logic handled by controller flash
        }
    });
};
</script>

<template>
    <Head title="Company Profile" />
    
    <AppLayout title="Company Profile">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto space-y-8 pb-20">
            <!-- Header Section -->
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-400">
                    <BuildingOffice2Icon class="h-8 w-8" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Organization Profile</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Manage your core company identity and global branding</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sidebar Branding -->
                <div class="lg:col-span-1">
                    <div class="glass-card rounded-2xl p-6 sticky top-8">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-2">Institutional Branding</h3>
                        
                        <div class="flex flex-col items-center">
                            <div class="group relative w-40 h-40 rounded-3xl bg-slate-100 dark:bg-slate-900/50 border-2 border-dashed border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden mb-6 shadow-xl transition-all hover:border-blue-500/50">
                                <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-4" />
                                <BuildingOffice2Icon v-else class="h-12 w-12 text-slate-300" />
                                
                                <label class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center cursor-pointer transition-all backdrop-blur-sm">
                                    <PhotoIcon class="h-8 w-8 text-white mb-2" />
                                    <span class="text-[10px] font-bold text-white uppercase tracking-widest">Update Logo</span>
                                    <input type="file" @change="onFileChange" class="hidden" accept="image/*" />
                                </label>
                            </div>
                            <div class="space-y-4 w-full">
                                <div class="p-4 bg-blue-500/5 rounded-xl border border-blue-500/10">
                                    <h5 class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Company Code</h5>
                                    <p class="text-lg font-mono font-black text-slate-900 dark:text-white">{{ company?.code || 'AUTO-GEN' }}</p>
                                </div>
                                <p class="text-[9px] text-slate-500 dark:text-slate-400 text-center uppercase font-bold tracking-tighter leading-relaxed px-2">
                                    Recommended: 512x512px PNG with transparent background for best display results.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Form Body -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- General Information -->
                    <div class="glass-card rounded-2xl p-8 space-y-6">
                        <div class="flex items-center gap-2 text-indigo-400 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <IdentificationIcon class="h-5 w-5" />
                            <h4 class="text-xs font-black uppercase tracking-widest">Legal Identity</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Trading Name</label>
                                <input v-model="form.name" type="text" class="form-input" placeholder="e.g. JICOS MFG" required />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Legal Entity Name</label>
                                <input v-model="form.legal_name" type="text" class="form-input" placeholder="e.g. PT Jidoka Integrated" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Tax ID (NPWP)</label>
                                <input v-model="form.tax_id" type="text" class="form-input" placeholder="00.000.000.0-000.000" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Website URL</label>
                                <div class="relative group">
                                    <GlobeAltIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 group-focus-within:text-blue-400 transition-colors" />
                                    <input v-model="form.website" type="url" class="form-input !pl-12" placeholder="https://..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="glass-card rounded-2xl p-8 space-y-6">
                        <div class="flex items-center gap-2 text-emerald-400 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <EnvelopeIcon class="h-5 w-5" />
                            <h4 class="text-xs font-black uppercase tracking-widest">Contact Framework</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Official Email</label>
                                <div class="relative group">
                                    <EnvelopeIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 group-focus-within:text-emerald-400 transition-colors" />
                                    <input v-model="form.email" type="email" class="form-input !pl-12" placeholder="hq@company.com" />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Official Phone</label>
                                <div class="relative group">
                                    <PhoneIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 group-focus-within:text-emerald-400 transition-colors" />
                                    <input v-model="form.phone" type="text" class="form-input !pl-12" placeholder="+62 21..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Location -->
                    <div class="glass-card rounded-2xl p-8 space-y-6">
                        <div class="flex items-center gap-2 text-cyan-400 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <MapPinIcon class="h-5 w-5" />
                            <h4 class="text-xs font-black uppercase tracking-widest">Global Location</h4>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Primary HQ Address</label>
                            <textarea v-model="form.address" rows="3" class="form-input" placeholder="Street name, Building No, Unit..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">City</label>
                                <input v-model="form.city" type="text" class="form-input" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">State/Province</label>
                                <input v-model="form.state" type="text" class="form-input" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Postal Code</label>
                                <input v-model="form.postal_code" type="text" class="form-input" />
                            </div>
                        </div>
                    </div>

                    <!-- Regional Settings -->
                    <div class="glass-card rounded-2xl p-8 space-y-6">
                        <div class="flex items-center gap-2 text-amber-400 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <ClockIcon class="h-5 w-5" />
                            <h4 class="text-xs font-black uppercase tracking-widest">Localization & Regional</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Base Currency</label>
                                <div class="relative">
                                    <BanknotesIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                                    <select v-model="form.currency" class="form-input !pl-12 appearance-none bg-inherit">
                                        <option value="IDR">Indonesian Rupiah (IDR)</option>
                                        <option value="USD">US Dollar (USD)</option>
                                        <option value="SGD">Singapore Dollar (SGD)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">System Timezone</label>
                                <div class="relative">
                                    <ClockIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
                                    <select v-model="form.timezone" class="form-input !pl-12 appearance-none bg-inherit">
                                        <option value="Asia/Jakarta">Jakarta (GMT+7)</option>
                                        <option value="Asia/Makassar">Makassar (GMT+8)</option>
                                        <option value="Asia/Jayapura">Jayapura (GMT+9)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Print Document Headers -->
                    <div class="glass-card rounded-2xl p-8 space-y-6">
                        <div class="flex items-center gap-2 text-fuchsia-400 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <DocumentTextIcon class="h-5 w-5" />
                            <h4 class="text-xs font-black uppercase tracking-widest">Print Document Headers</h4>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">
                            Configure the header text that appears on printed documents like Invoices, Sales Orders, and Delivery Orders.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Company Logo Text</label>
                                <input v-model="form.company_logo_text" type="text" class="form-input" placeholder="jidoka" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Company Full Name</label>
                                <input v-model="form.company_full_name" type="text" class="form-input" placeholder="PT. JIDOKA RESULT INDONESIA" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Document Logo (Image)</label>
                            <input type="file" @input="form.print_logo_file = $event.target.files[0]" class="form-input !py-2.5" accept="image/*" />
                            <p class="text-[10px] text-slate-400 mt-1 ml-1">Upload a specific logo for printed documents (Invoices, PO, DO). Leave empty to keep current.</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Company Address & Contact Block</label>
                            <textarea v-model="form.company_address" rows="5" class="form-input" placeholder="Kawasan Industri JABABEKA I..."></textarea>
                            <p class="text-[10px] text-slate-400 mt-1 ml-1">This text will be printed exactly as typed (preserves line breaks) on the right side of the logo.</p>
                        </div>
                    </div>

                    <!-- Helpdesk Notification Settings -->
                    <div class="glass-card rounded-2xl p-8 space-y-6">
                        <div class="flex items-center gap-2 text-rose-400 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <EnvelopeIcon class="h-5 w-5" />
                            <h4 class="text-xs font-black uppercase tracking-widest">Helpdesk Notifications</h4>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">
                            Configure the central IT support contact. All new unassigned tickets will be sent to these contacts.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Central WhatsApp Number</label>
                                <div class="relative group">
                                    <PhoneIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 group-focus-within:text-rose-400 transition-colors" />
                                    <input v-model="form.helpdesk_wa_number" type="text" class="form-input !pl-12" placeholder="+62..." />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Central Email Address</label>
                                <div class="relative group">
                                    <EnvelopeIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 group-focus-within:text-rose-400 transition-colors" />
                                    <input v-model="form.helpdesk_email_address" type="email" class="form-input !pl-12" placeholder="it.support@..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="inline-flex items-center gap-3 px-10 py-4 bg-blue-600 hover:bg-blue-500 text-slate-900 dark:text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-2xl shadow-blue-500/20 transition-all active:scale-95 disabled:opacity-50 group"
                        >
                            <CheckBadgeIcon class="h-5 w-5 transition-transform group-hover:scale-125" />
                            Synchronize Profile Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

.form-input {
    @apply block w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-white/5 rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all duration-200;
}
textarea.form-input {
    @apply resize-none;
}
select.form-input {
    @apply cursor-pointer;
}
</style>
