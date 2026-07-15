@extends('layouts.public')

@section('title', 'Başvuru Oluştur - Şikayetçiyim Kıbrıs')

@section('content')
    <section class="page-hero compact">
        <p class="eyebrow">Başvuru oluştur</p>
        <h1>Adım adım ilerle, doğru yere ulaşsın.</h1>
        <p class="lead" data-intake-lead>Önce başvuru türünü seç, sonra bölgeyi veya ilgili kurumu belirle. Detayları son adımda alıyoruz.</p>
    </section>

    <section class="wizard-shell" data-wizard>
        <aside class="wizard-progress" aria-label="Başvuru adımları">
            <button class="wizard-step-indicator is-active" type="button" data-jump-step="1">
                <span>1</span>
                <strong>Tür</strong>
            </button>
            <button class="wizard-step-indicator" type="button" data-jump-step="2">
                <span>2</span>
                <strong>Konu / Yer</strong>
            </button>
            <button class="wizard-step-indicator" type="button" data-jump-step="3">
                <span>3</span>
                <strong>Detaylar</strong>
            </button>
        </aside>

        <form class="public-form wizard-form" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div class="alert alert-error">
                    <strong>Formda düzeltilmesi gereken alanlar var.</strong>
                    <span>Lütfen adımları kontrol et.</span>
                </div>
            @endif

            <section class="wizard-step is-active" data-step="1">
                <div class="step-heading">
                    <p class="eyebrow">1. adım</p>
                    <h2>Ne yapmak istiyorsun?</h2>
                </div>

                <div class="choice-grid">
                    <label class="choice-card">
                        <input type="radio" name="intake_type" value="complaint" data-intake-option @checked(old('intake_type', 'complaint') === 'complaint') required>
                        <span class="choice-icon">!</span>
                        <strong>İtiraz yapacağım</strong>
                        <small>Hizmet, kurum, şirket veya bölgeyle ilgili yaşadığın sorunu bildir.</small>
                    </label>

                    <label class="choice-card">
                        <input type="radio" name="intake_type" value="report" data-intake-option @checked(old('intake_type') === 'report') required>
                        <span class="choice-icon">#</span>
                        <strong>İhbar yapacağım</strong>
                        <small>Yolsuzluk, usulsüzlük veya kamu yararına incelenmesi gereken bilgiyi ilet.</small>
                    </label>
                </div>
                @error('intake_type') <small class="field-error">{{ $message }}</small> @enderror

                <div class="wizard-actions">
                    <button class="button button-primary" type="button" data-next-step>Devam Et</button>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="step-heading">
                    <p class="eyebrow">2. adım</p>
                    <h2 data-step-two-title>Nereyle ilgili?</h2>
                    <p>Konu başlığını, bölgeyi ve ilgili kurumu seç. Bölge veya kurum/şirketten en az biri gerekli.</p>
                </div>

                <label class="field">
                    <span>İtiraz Konusu</span>
                    <select name="issue_area" required>
                        <option value="">Konu seç</option>
                        @foreach ($issueAreas as $key => $label)
                            <option value="{{ $key }}" @selected(old('issue_area') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('issue_area') <small>{{ $message }}</small> @enderror
                </label>

                <div class="split-fields">
                    <label class="field" data-region-field>
                        <span>Bölge</span>
                        <select name="region_id" data-region-select>
                            <option value="">Bölge seçilmedi</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}" data-parent-id="{{ $region->parent_id }}" @selected((string) old('region_id') === (string) $region->id)>
                                    {{ $region->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('region_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="field" data-entity-field>
                        <span data-entity-label>Kurum veya Şirket</span>
                        <select name="entity_id" data-entity-select>
                            <option value="">Kurum / şirket seçilmedi</option>
                            @foreach ($entityGroups as $category => $categoryEntities)
                                <optgroup label="{{ $category }}">
                                    @foreach ($categoryEntities as $entity)
                                        <option value="{{ $entity->id }}" data-category="{{ $entity->category }}" data-name="{{ $entity->name }}" data-region-id="{{ $entity->region_id }}" @selected((string) old('entity_id') === (string) $entity->id)>
                                            {{ $entity->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('entity_id') <small>{{ $message }}</small> @enderror
                        <small data-entity-hint>Önce konu başlığını seç; kurum listesi ona göre daralır.</small>
                    </label>
                </div>

                <div class="manual-target">
                    <strong>Listede yoksa sorun değil.</strong>
                    <span>Son adımda başlık ve açıklama içinde ilgili yerin adını yazabilirsin.</span>
                </div>

                <div class="wizard-actions">
                    <button class="button button-secondary" type="button" data-prev-step>Geri</button>
                    <button class="button button-primary" type="button" data-next-step>Detaylara Geç</button>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="step-heading">
                    <p class="eyebrow">3. adım</p>
                    <h2 data-detail-title>Detayları anlat.</h2>
                </div>

                <label class="field">
                    <span>Başlık</span>
                    <input name="title" value="{{ old('title') }}" required maxlength="180" data-title-input placeholder="Örnek: Girne bölgesinde su kesintisi itirazı">
                    @error('title') <small>{{ $message }}</small> @enderror
                </label>

                <label class="field">
                    <span>Açıklama</span>
                    <textarea name="body" required maxlength="12000" rows="8" data-body-input placeholder="Ne oldu, nerede oldu, kimler dahil, varsa tarih ve belge detaylarını yaz.">{{ old('body') }}</textarea>
                    @error('body') <small>{{ $message }}</small> @enderror
                </label>

                <label class="field">
                    <span>İletişim Bilgisi <em>opsiyonel</em></span>
                    <input name="reporter_contact" value="{{ old('reporter_contact') }}" placeholder="E-posta veya telefon">
                    <small>Eksik bilgi gerekirse sana ulaşmak için kullanılır; kamuya açık gösterilmez.</small>
                    @error('reporter_contact') <small>{{ $message }}</small> @enderror
                </label>

                <div class="consent-box">
                    <label class="check-field">
                        <input type="checkbox" name="identity_disclosed" value="1" @checked(old('identity_disclosed'))>
                        <span>İsmimin yayınlanmasına izin veriyorum.</span>
                    </label>

                    <label class="field">
                        <span>Yayında Kullanılacak İsim</span>
                        <input name="reporter_name" value="{{ old('reporter_name') }}" placeholder="Ad soyad veya kurum adı">
                        @error('reporter_name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="check-field">
                        <input type="checkbox" name="disclosure_consent" value="1" @checked(old('disclosure_consent'))>
                        <span>{{ __('reports.disclosure_consent_text') }}</span>
                    </label>
                    @error('disclosure_consent') <small class="field-error">{{ $message }}</small> @enderror
                </div>

                <label class="field file-field">
                    <span>Kanıt Dosyaları</span>
                    <input type="file" name="evidence_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.mp4" data-file-input>
                    <small>PDF, JPG, PNG veya MP4. Dosya başına en fazla 25 MB.</small>
                    <div class="file-list" data-file-list hidden></div>
                    @error('evidence_files') <small>{{ $message }}</small> @enderror
                    @error('evidence_files.*') <small>{{ $message }}</small> @enderror
                </label>

                <div class="wizard-actions">
                    <button class="button button-secondary" type="button" data-prev-step>Geri</button>
                    <button class="button button-primary form-submit" type="submit">Başvuruyu Gönder</button>
                </div>
            </section>
        </form>
    </section>

    <script>
        (() => {
            const wizard = document.querySelector('[data-wizard]');
            if (!wizard) return;

            const steps = [...wizard.querySelectorAll('[data-step]')];
            const indicators = [...wizard.querySelectorAll('[data-jump-step]')];
            const intakeOptions = [...wizard.querySelectorAll('[data-intake-option]')];
            const issueAreaSelect = wizard.querySelector('select[name="issue_area"]');
            const regionField = wizard.querySelector('[data-region-field]');
            const entityField = wizard.querySelector('[data-entity-field]');
            const regionSelect = wizard.querySelector('[data-region-select]');
            const entitySelect = wizard.querySelector('[data-entity-select]');
            const entityLabel = wizard.querySelector('[data-entity-label]');
            const entityHint = wizard.querySelector('[data-entity-hint]');
            const fileInput = wizard.querySelector('[data-file-input]');
            const fileList = wizard.querySelector('[data-file-list]');
            const copy = {
                complaint: {
                    lead: 'İtirazını doğru kuruma ulaştırmak için tür, konu, bölge ve detayları adım adım alıyoruz.',
                    stepTwo: 'İtiraz hangi konu ve yerle ilgili?',
                    detail: 'İtirazını detaylandır.',
                    title: 'Örnek: Girne bölgesinde su kesintisi itirazı',
                    body: 'Yaşadığın sorunu, tarihini, yeri ve varsa daha önce başvurduğun kurumları yaz.'
                },
                report: {
                    lead: 'İhbarını güvenli şekilde kayda almak için tür, konu, bölge ve kanıt bilgilerini adım adım alıyoruz.',
                    stepTwo: 'İhbar hangi konu ve kurumla ilgili?',
                    detail: 'İhbar detaylarını anlat.',
                    title: 'Örnek: Belediyede usulsüz ihale iddiası',
                    body: 'Ne oldu, nerede oldu, kimler dahil, varsa tarih ve belge detaylarını yaz.'
                }
            };
            let current = {{ $errors->any() ? 3 : 1 }};

            const showStep = (step) => {
                current = Math.max(1, Math.min(step, steps.length));
                steps.forEach((panel) => panel.classList.toggle('is-active', Number(panel.dataset.step) === current));
                indicators.forEach((button) => {
                    const index = Number(button.dataset.jumpStep);
                    button.classList.toggle('is-active', index === current);
                    button.classList.toggle('is-complete', index < current);
                });
            };

            const selectedIntake = () => wizard.querySelector('input[name="intake_type"]:checked')?.value || 'complaint';
            const isCitizenshipIssue = () => issueAreaSelect.value === 'citizenship_residency';
            const isHealthIssue = () => issueAreaSelect.value === 'health';
            const requiresRegionThenEntity = () => ['roads_asphalt', 'municipal_services', 'garbage_environment', 'water_sewerage'].includes(issueAreaSelect.value);
            const requiresEntityOnly = () => ['health'].includes(issueAreaSelect.value);
            const normalizeText = (value) => (value || '')
                .toLocaleLowerCase('tr-TR')
                .replaceAll('_', ' ')
                .replaceAll('ı', 'i')
                .replaceAll('ğ', 'g')
                .replaceAll('ü', 'u')
                .replaceAll('ş', 's')
                .replaceAll('ö', 'o')
                .replaceAll('ç', 'c');
            const issueRules = {
                citizenship_residency: { categories: ['bakanlık', 'kamu kurumu'], nameIncludes: ['icisleri', 'nufus', 'muhaceret'], hint: 'İçişleri Bakanlığı, Nüfus Kayıt Dairesi veya Muhaceret Dairesi seçilebilir.' },
                roads_asphalt: { categories: ['belediye'], suggestMunicipality: true, hint: 'Bölge seçince ilgili belediye otomatik önerilir; istersen başka belediye de seçebilirsin.' },
                municipal_services: { categories: ['belediye'], suggestMunicipality: true, hint: 'Bölge seçince ilgili belediye otomatik önerilir; istersen değiştirebilirsin.' },
                garbage_environment: { categories: ['belediye'], suggestMunicipality: true, hint: 'Çöp, temizlik ve çevre sorunlarında belediyeler listelenir; bölge seçince ilgili belediye önerilir.' },
                water_sewerage: { categories: ['belediye'], suggestMunicipality: true, hint: 'Su ve altyapı sorunlarında bölge seçince ilgili belediye otomatik önerilir; başka belediye de seçebilirsin.' },
                transport_traffic: { categories: ['belediye', 'hizmet'], suggestMunicipality: true, nameIncludes: ['toplu tasima', 'durak'], hint: 'Bölge seçince ilgili belediye önerilir; ulaşım hizmetlerini de seçebilirsin.' },
                zoning_construction: { categories: ['belediye', 'kamu kurumu'], suggestMunicipality: true, nameIncludes: ['sehir planlama', 'tapu', 'kadastro'], hint: 'Bölge seçince ilgili belediye önerilir; imar kurumları da listede kalır.' },
                electricity: { categories: ['belediye', 'kamu kurumu', 'hizmet'], suggestMunicipality: true, nameIncludes: ['elektrik', 'kibtek', 'kibris turk elektrik'], hint: 'Elektrik ve aydınlatma sorunlarında bölge seçince belediye önerilir; KIBTEK ve elektrik hizmetlerini de seçebilirsin.' },
                health: { categories: ['sağlık', 'bakanlık'], nameIncludes: ['saglik', 'hastane', 'klinik', 'ocagi', 'ocak'], hint: 'Hastane, sağlık ocağı veya Sağlık Bakanlığı seçebilirsin.' },
                education: { categories: ['eğitim', 'bakanlık'], nameIncludes: ['egitim', 'okul', 'universite', 'ogrenci'], hint: 'Eğitim konularında Milli Eğitim ve eğitim hizmetleri gösterilir.' },
                labor_social_security: { categories: ['bakanlık', 'kamu kurumu'], nameIncludes: ['calisma', 'sosyal guvenlik', 'sigorta', 'ihtiyat'], hint: 'Çalışma izni, iş ve sosyal güvenlik konularında ilgili çalışma kurumları gösterilir.' },
                police_security: { categories: ['kamu kurumu'], nameIncludes: ['polis', 'mahkeme'], hint: 'Güvenlik ve adli süreçlerde polis ve mahkeme kurumları gösterilir.' },
                public_procurement: { categories: ['bakanlık', 'belediye', 'kamu kurumu'], suggestMunicipality: true, hint: 'İhale ve kamu zararı konularında bölge seçince belediye önerilir; kamu kurumları ve bakanlıklar da listede kalır.' },
                consumer_company: { categories: ['şirket', 'hizmet', 'sağlık', 'eğitim'], hint: 'Şirket ve tüketici mağduriyetlerinde özel hizmet ve şirket başlıkları gösterilir.' },
            };
            const selectedRegionIds = () => {
                const selected = regionSelect.selectedOptions[0];
                return [regionSelect.value, selected?.dataset.parentId].filter(Boolean);
            };
            const selectedRegionName = () => normalizeText(regionSelect.selectedOptions[0]?.textContent || '');
            const municipalityBySettlement = {
                'lefkosa': 'lefkosa turk belediyesi',
                'lefkosa merkez': 'lefkosa turk belediyesi',
                'gonyeli': 'gonyeli alaykoy belediyesi',
                'alaykoy': 'gonyeli alaykoy belediyesi',
                'degirmenlik': 'degirmenlik akincilar belediyesi',
                'akincilar': 'degirmenlik akincilar belediyesi',
                'hamitkoy': 'lefkosa turk belediyesi',
                'haspolat': 'lefkosa turk belediyesi',
                'metehan': 'lefkosa turk belediyesi',
                'kucuk kaymakli': 'lefkosa turk belediyesi',
                'ortakoy': 'lefkosa turk belediyesi',
                'gazimagusa': 'gazimagusa belediyesi',
                'gazimagusa merkez': 'gazimagusa belediyesi',
                'yenibogazici': 'yenibogazici belediyesi',
                'beyarmudu': 'beyarmudu belediyesi',
                'akdogan': 'mesarya belediyesi',
                'vadili': 'mesarya belediyesi',
                'inonu': 'mesarya belediyesi',
                'gecitkale': 'gecitkale serdarli belediyesi',
                'serdarli': 'gecitkale serdarli belediyesi',
                'mormenekse': 'yenibogazici belediyesi',
                'tuzla': 'gazimagusa belediyesi',
                'girne': 'girne belediyesi',
                'girne merkez': 'girne belediyesi',
                'dikmen': 'dikmen belediyesi',
                'catalkoy': 'catalkoy esentepe belediyesi',
                'esentepe': 'catalkoy esentepe belediyesi',
                'lapta': 'lapta alsancak camlibel belediyesi',
                'alsancak': 'lapta alsancak camlibel belediyesi',
                'camlibel': 'lapta alsancak camlibel belediyesi',
                'karsiyaka': 'lapta alsancak camlibel belediyesi',
                'karaoglanoglu': 'girne belediyesi',
                'ozankoy': 'girne belediyesi',
                'guzelyurt': 'guzelyurt belediyesi',
                'guzelyurt merkez': 'guzelyurt belediyesi',
                'lefke': 'lefke belediyesi',
                'lefke merkez': 'lefke belediyesi',
                'iskele': 'iskele belediyesi',
                'iskele merkez': 'iskele belediyesi',
                'mehmetcik': 'mehmetcik buyukkonuk belediyesi',
                'buyukkonuk': 'mehmetcik buyukkonuk belediyesi',
                'yeni erenkoy': 'yenierenkoy dipkarpaz belediyesi',
                'dipkarpaz': 'yenierenkoy dipkarpaz belediyesi',
                'tatlisu': 'tatlisu belediyesi',
            };

            const optionMatchesIssue = (option) => {
                if (!option.value) return true;

                const issue = issueAreaSelect.value;
                if (!issue) return true;

                const rule = issueRules[issue];
                if (!rule) return true;

                const category = normalizeText(option.dataset.category);
                const name = normalizeText(option.dataset.name);
                const categoryMatch = (rule.categories || []).map(normalizeText).includes(category);
                const nameMatch = (rule.nameIncludes || []).some((needle) => name.includes(needle));

                return categoryMatch || nameMatch;
            };

            const optionMatchesSelectedRegion = (option) => {
                const regionIds = selectedRegionIds();
                if (regionIds.length === 0) return false;
                const optionRegion = option.dataset.regionId;
                return Boolean(optionRegion && regionIds.includes(optionRegion));
            };

            const suggestEntityForRegion = () => {
                const rule = issueRules[issueAreaSelect.value];
                if (!rule?.suggestMunicipality || !regionSelect.value) return;

                const municipalityOptions = [...entitySelect.options].filter((option) => (
                    option.value
                    && !option.hidden
                    && normalizeText(option.dataset.category) === 'belediye'
                ));

                const regionName = selectedRegionName();
                const mappedMunicipalityName = municipalityBySettlement[regionName];
                const mappedMunicipality = municipalityOptions.find((option) => {
                    return normalizeText(option.dataset.name) === mappedMunicipalityName;
                });

                const exactRegionMunicipality = municipalityOptions.find((option) => {
                    const optionName = normalizeText(option.dataset.name);

                    return optionName === `${regionName} belediyesi`
                        || optionName.startsWith(`${regionName} belediyesi`)
                        || optionName.includes(`${regionName} belediyesi`)
                        || optionName.includes(regionName);
                });

                const sameRegionMunicipality = municipalityOptions.find(optionMatchesSelectedRegion);
                const suggested = mappedMunicipality || exactRegionMunicipality || sameRegionMunicipality || municipalityOptions[0];

                if (suggested) {
                    entitySelect.value = suggested.value;
                }
            };

            const syncOptGroups = () => {
                [...entitySelect.querySelectorAll('optgroup')].forEach((group) => {
                    const visibleOptions = [...group.querySelectorAll('option')].filter((option) => !option.hidden);
                    group.hidden = visibleOptions.length === 0;
                });
            };

            const syncIntakeCopy = () => {
                const text = copy[selectedIntake()];
                wizard.ownerDocument.querySelector('[data-intake-lead]').textContent = text.lead;
                wizard.querySelector('[data-step-two-title]').textContent = text.stepTwo;
                wizard.querySelector('[data-detail-title]').textContent = text.detail;
                wizard.querySelector('[data-title-input]').placeholder = text.title;
                wizard.querySelector('[data-body-input]').placeholder = text.body;
            };

            const syncEntityOptions = () => {
                [...entitySelect.options].forEach((option) => {
                    if (!option.value) return;
                    option.hidden = !optionMatchesIssue(option);
                });

                if (entitySelect.selectedOptions[0]?.hidden) {
                    entitySelect.value = '';
                }

                syncOptGroups();
                suggestEntityForRegion();
            };

            const syncIssueAreaRules = () => {
                const citizenship = isCitizenshipIssue();
                const health = isHealthIssue();
                const rule = issueRules[issueAreaSelect.value];
                regionField.hidden = citizenship || health;
                regionSelect.disabled = citizenship || health;
                entitySelect.disabled = false;
                entityLabel.textContent = health ? 'Hastane veya Sağlık Ocağı' : 'Kurum veya Şirket';

                if (citizenship) {
                    regionSelect.value = '';
                    wizard.querySelector('[data-title-input]').placeholder = 'Örnek: 11 yıldır ikamet etmeme rağmen vatandaşlık sürecim ilerlemiyor';
                    wizard.querySelector('[data-body-input]').placeholder = 'Kaç yıldır ikamet ettiğini, muhaceret durumunu, hangi evrakların tamam olduğunu, başvurunun nerede beklediğini ve sana verilen cevapları yaz.';
                    entityHint.textContent = rule?.hint || 'Vatandaşlık sürecinle ilgili kurumu seçebilirsin.';
                } else if (health) {
                    regionSelect.value = '';
                    wizard.querySelector('[data-title-input]').placeholder = 'Örnek: Hastane randevusu veya sağlık ocağı hizmetiyle ilgili sorun';
                    wizard.querySelector('[data-body-input]').placeholder = 'Hangi hastane veya sağlık ocağı, ne zaman, hangi hizmette sorun yaşadığını ve varsa başvuru/rapor detaylarını yaz.';
                    entityHint.textContent = rule?.hint || 'Hastane veya sağlık ocağı seçebilirsin.';
                } else {
                    regionSelect.disabled = false;
                    regionField.hidden = false;
                    entityLabel.textContent = 'Kurum veya Şirket';
                    entityHint.textContent = rule?.hint || 'Kurum listesi seçtiğin konuya göre daraltıldı.';
                    syncIntakeCopy();
                }

                syncEntityOptions();
            };

            const syncFileList = () => {
                const files = [...fileInput.files];
                fileList.hidden = files.length === 0;
                fileList.replaceChildren(...files.map((file) => {
                    const item = document.createElement('span');
                    item.textContent = file.name;
                    return item;
                }));
            };

            wizard.addEventListener('click', (event) => {
                const next = event.target.closest('[data-next-step]');
                const prev = event.target.closest('[data-prev-step]');
                const jump = event.target.closest('[data-jump-step]');

                if (next) {
                    event.preventDefault();
                    if (current === 1 && !wizard.querySelector('input[name="intake_type"]:checked')) {
                        wizard.querySelector('input[name="intake_type"]').focus();
                        return;
                    }
                    if (current === 2 && !wizard.querySelector('select[name="issue_area"]').value) {
                        wizard.querySelector('select[name="issue_area"]').focus();
                        return;
                    }
                    if (current === 2 && requiresRegionThenEntity() && !regionSelect.value) {
                        regionSelect.focus();
                        return;
                    }
                    if (current === 2 && requiresEntityOnly() && !entitySelect.value) {
                        entitySelect.focus();
                        return;
                    }
                    showStep(current + 1);
                    return;
                }

                if (prev) {
                    event.preventDefault();
                    showStep(current - 1);
                    return;
                }

                if (jump) {
                    event.preventDefault();
                    showStep(Number(jump.dataset.jumpStep));
                }
            });

            intakeOptions.forEach((option) => option.addEventListener('change', () => {
                syncIntakeCopy();
                syncIssueAreaRules();
            }));
            issueAreaSelect.addEventListener('change', syncIssueAreaRules);
            regionSelect.addEventListener('change', syncEntityOptions);
            fileInput.addEventListener('change', syncFileList);
            wizard.querySelector('form').addEventListener('submit', () => {
                entitySelect.disabled = false;
            });
            syncIntakeCopy();
            syncIssueAreaRules();
            showStep(current);
        })();
    </script>
@endsection
