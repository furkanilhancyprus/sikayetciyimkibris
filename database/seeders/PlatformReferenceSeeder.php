<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->renameExistingReferenceData();

        $districts = ['Lefkoşa', 'Gazimağusa', 'Girne', 'Güzelyurt', 'İskele', 'Lefke'];
        $districtIds = [];

        foreach ($districts as $district) {
            $districtIds[$district] = Region::query()
                ->updateOrCreate(['name' => $district], ['type' => 'il'])
                ->id;
        }

        $settlements = [
            'Lefkoşa' => ['Lefkoşa Merkez', 'Gönyeli', 'Alayköy', 'Değirmenlik', 'Akıncılar', 'Hamitköy', 'Haspolat', 'Metehan', 'Küçük Kaymaklı', 'Ortaköy'],
            'Gazimağusa' => ['Gazimağusa Merkez', 'Yeniboğaziçi', 'Beyarmudu', 'Akdoğan', 'Vadili', 'İnönü', 'Geçitkale', 'Serdarlı', 'Mormenekşe', 'Tuzla'],
            'Girne' => ['Girne Merkez', 'Dikmen', 'Çatalköy', 'Esentepe', 'Lapta', 'Alsancak', 'Çamlıbel', 'Karşıyaka', 'Karaoğlanoğlu', 'Ozanköy'],
            'Güzelyurt' => ['Güzelyurt Merkez', 'Bostancı', 'Zümrütköy', 'Yayla', 'Serhatköy', 'Gayretköy'],
            'İskele' => ['İskele Merkez', 'Mehmetçik', 'Büyükkonuk', 'Yeni Erenköy', 'Dipkarpaz', 'Kalecik', 'Boğaz', 'Kumköy', 'Ziyamet', 'Gelincik'],
            'Lefke' => ['Lefke Merkez', 'Gemikonağı', 'Yeşilyurt', 'Yedidalga', 'Yeşilırmak', 'Cengizköy'],
        ];

        foreach ($settlements as $district => $names) {
            foreach ($names as $name) {
                Region::query()->updateOrCreate(
                    ['name' => $name],
                    ['type' => 'belde', 'parent_id' => $districtIds[$district]]
                );
            }
        }

        $municipalities = [
            ['Lefkoşa Türk Belediyesi', 'Lefkoşa'],
            ['Gönyeli-Alayköy Belediyesi', 'Lefkoşa'],
            ['Değirmenlik-Akıncılar Belediyesi', 'Lefkoşa'],
            ['Gazimağusa Belediyesi', 'Gazimağusa'],
            ['Yeniboğaziçi Belediyesi', 'Gazimağusa'],
            ['Beyarmudu Belediyesi', 'Gazimağusa'],
            ['Mesarya Belediyesi', 'Gazimağusa'],
            ['Geçitkale-Serdarlı Belediyesi', 'Gazimağusa'],
            ['Girne Belediyesi', 'Girne'],
            ['Dikmen Belediyesi', 'Girne'],
            ['Çatalköy-Esentepe Belediyesi', 'Girne'],
            ['Lapta-Alsancak-Çamlıbel Belediyesi', 'Girne'],
            ['Güzelyurt Belediyesi', 'Güzelyurt'],
            ['Lefke Belediyesi', 'Lefke'],
            ['İskele Belediyesi', 'İskele'],
            ['Mehmetçik-Büyükkonuk Belediyesi', 'İskele'],
            ['Yenierenköy-Dipkarpaz Belediyesi', 'İskele'],
            ['Tatlısu Belediyesi', 'İskele'],
        ];

        foreach ($municipalities as [$name, $district]) {
            Entity::query()->updateOrCreate(
                ['name' => $name],
                ['category' => 'belediye', 'region_id' => $districtIds[$district]]
            );
        }

        $publicEntities = [
            ['KKTC Başbakanlık', 'bakanlık', 'Lefkoşa'],
            ['İçişleri Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Maliye Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Milli Eğitim Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Sağlık Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Bayındırlık ve Ulaştırma Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Turizm, Kültür, Gençlik ve Çevre Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Çalışma ve Sosyal Güvenlik Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Tarım ve Doğal Kaynaklar Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Ekonomi ve Enerji Bakanlığı', 'bakanlık', 'Lefkoşa'],
            ['Nüfus Kayıt Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Muhaceret Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Tapu ve Kadastro Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Karayolları Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Şehir Planlama Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Sosyal Sigortalar Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['İhtiyat Sandığı Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Çalışma Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Gelir ve Vergi Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Gümrük ve Rüsumat Dairesi', 'kamu kurumu', 'Lefkoşa'],
            ['Polis Genel Müdürlüğü', 'kamu kurumu', 'Lefkoşa'],
            ['Mahkemeler', 'kamu kurumu', 'Lefkoşa'],
            ['Kıbrıs Türk Elektrik Kurumu', 'kamu kurumu', null],
            ['Su İşleri Dairesi', 'kamu kurumu', null],
            ['Telekomünikasyon Dairesi', 'kamu kurumu', null],
            ['Devlet Hastanesi - Lefkoşa', 'sağlık', 'Lefkoşa'],
            ['Devlet Hastanesi - Gazimağusa', 'sağlık', 'Gazimağusa'],
            ['Devlet Hastanesi - Girne', 'sağlık', 'Girne'],
            ['Devlet Hastanesi - Güzelyurt', 'sağlık', 'Güzelyurt'],
            ['Devlet Hastanesi - İskele', 'sağlık', 'İskele'],
            ['Devlet Hastanesi - Lefke', 'sağlık', 'Lefke'],
            ['Sağlık Ocağı - Lefkoşa', 'sağlık', 'Lefkoşa'],
            ['Sağlık Ocağı - Gazimağusa', 'sağlık', 'Gazimağusa'],
            ['Sağlık Ocağı - Girne', 'sağlık', 'Girne'],
            ['Sağlık Ocağı - Güzelyurt', 'sağlık', 'Güzelyurt'],
            ['Sağlık Ocağı - İskele', 'sağlık', 'İskele'],
            ['Sağlık Ocağı - Lefke', 'sağlık', 'Lefke'],
        ];

        foreach ($publicEntities as [$name, $category, $district]) {
            Entity::query()->updateOrCreate(
                ['name' => $name],
                ['category' => $category, 'region_id' => $district ? $districtIds[$district] : null]
            );
        }

        $serviceCompanies = [
            ['Elektrik fatura ve kesinti şikâyetleri', 'hizmet'],
            ['Su abonelik ve kesinti şikâyetleri', 'hizmet'],
            ['İnternet ve telefon hizmetleri', 'hizmet'],
            ['Toplu taşıma ve durak hizmetleri', 'hizmet'],
            ['Özel üniversite ve öğrenci hizmetleri', 'eğitim'],
            ['Özel hastane ve klinik hizmetleri', 'sağlık'],
            ['Market, restoran ve tüketici şikâyetleri', 'şirket'],
            ['İnşaat, site ve apartman yönetimi', 'şirket'],
        ];

        foreach ($serviceCompanies as [$name, $category]) {
            Entity::query()->updateOrCreate(
                ['name' => $name],
                ['category' => $category, 'region_id' => null]
            );
        }

        $this->deduplicateReferenceData();
    }

    private function renameExistingReferenceData(): void
    {
        $regions = [
            'Lefkosa' => 'Lefkoşa',
            'Gazimagusa' => 'Gazimağusa',
            'Guzelyurt' => 'Güzelyurt',
            'Iskele' => 'İskele',
            'Lefkosa Merkez' => 'Lefkoşa Merkez',
            'Gonyeli' => 'Gönyeli',
            'Alaykoy' => 'Alayköy',
            'Degirmenlik' => 'Değirmenlik',
            'Akincilar' => 'Akıncılar',
            'Hamitkoy' => 'Hamitköy',
            'Kucuk Kaymakli' => 'Küçük Kaymaklı',
            'Ortakoy' => 'Ortaköy',
            'Gazimagusa Merkez' => 'Gazimağusa Merkez',
            'Yenibogazici' => 'Yeniboğaziçi',
            'Akdogan' => 'Akdoğan',
            'Inonu' => 'İnönü',
            'GeÃ§itkale' => 'Geçitkale',
            'Serdarli' => 'Serdarlı',
            'Mormenekse' => 'Mormenekşe',
            'Catalkoy' => 'Çatalköy',
            'Camlibel' => 'Çamlıbel',
            'Karsiyaka' => 'Karşıyaka',
            'Karaoglanoglu' => 'Karaoğlanoğlu',
            'Guzelyurt Merkez' => 'Güzelyurt Merkez',
            'Bostanci' => 'Bostancı',
            'Zumrutkoy' => 'Zümrütköy',
            'Serhatkoy' => 'Serhatköy',
            'Gayretkoy' => 'Gayretköy',
            'Iskele Merkez' => 'İskele Merkez',
            'Mehmetcik' => 'Mehmetçik',
            'Buyukkonuk' => 'Büyükkonuk',
            'Yeni Erenkoy' => 'Yeni Erenköy',
            'Bogaz' => 'Boğaz',
            'Kumkoy' => 'Kumköy',
            'Gemikonagi' => 'Gemikonağı',
            'Yesilyurt' => 'Yeşilyurt',
            'Yesilirmak' => 'Yeşilırmak',
            'Cengizkoy' => 'Cengizköy',
        ];

        foreach ($regions as $from => $to) {
            Region::query()->where('name', $from)->update(['name' => $to]);
        }

        $entities = [
            'Lefkosa Turk Belediyesi' => 'Lefkoşa Türk Belediyesi',
            'Gonyeli-Alaykoy Belediyesi' => 'Gönyeli-Alayköy Belediyesi',
            'Degirmenlik-Akincilar Belediyesi' => 'Değirmenlik-Akıncılar Belediyesi',
            'Gazimagusa Belediyesi' => 'Gazimağusa Belediyesi',
            'Yenibogazici Belediyesi' => 'Yeniboğaziçi Belediyesi',
            'Gecitkale-Serdarli Belediyesi' => 'Geçitkale-Serdarlı Belediyesi',
            'Catalkoy-Esentepe Belediyesi' => 'Çatalköy-Esentepe Belediyesi',
            'Lapta-Alsancak-Camlibel Belediyesi' => 'Lapta-Alsancak-Çamlıbel Belediyesi',
            'Guzelyurt Belediyesi' => 'Güzelyurt Belediyesi',
            'Iskele Belediyesi' => 'İskele Belediyesi',
            'Mehmetcik-Buyukkonuk Belediyesi' => 'Mehmetçik-Büyükkonuk Belediyesi',
            'TatlÄ±su Belediyesi' => 'Tatlısu Belediyesi',
            'KKTC Basbakanlik' => 'KKTC Başbakanlık',
            'Icisleri Bakanligi' => 'İçişleri Bakanlığı',
            'Maliye Bakanligi' => 'Maliye Bakanlığı',
            'Ekonomi ve Enerji Bakanligi' => 'Ekonomi ve Enerji Bakanlığı',
            'Milli Egitim Bakanligi' => 'Milli Eğitim Bakanlığı',
            'Saglik Bakanligi' => 'Sağlık Bakanlığı',
            'Bayindirlik ve Ulastirma Bakanligi' => 'Bayındırlık ve Ulaştırma Bakanlığı',
            'Turizm, Kultur, Genclik ve Cevre Bakanligi' => 'Turizm, Kültür, Gençlik ve Çevre Bakanlığı',
            'Calisma ve Sosyal Guvenlik Bakanligi' => 'Çalışma ve Sosyal Güvenlik Bakanlığı',
            'Tarim ve Dogal Kaynaklar Bakanligi' => 'Tarım ve Doğal Kaynaklar Bakanlığı',
            'Nufus Kayit Dairesi' => 'Nüfus Kayıt Dairesi',
            'Sehir Planlama Dairesi' => 'Şehir Planlama Dairesi',
            'Ihtiyat Sandigi Dairesi' => 'İhtiyat Sandığı Dairesi',
            'Calisma Dairesi' => 'Çalışma Dairesi',
            'Gumruk ve Rüsumat Dairesi' => 'Gümrük ve Rüsumat Dairesi',
            'Gumruk ve RÃ¼sumat Dairesi' => 'Gümrük ve Rüsumat Dairesi',
            'Polis Genel Mudurlugu' => 'Polis Genel Müdürlüğü',
            'Kibris Turk Elektrik Kurumu' => 'Kıbrıs Türk Elektrik Kurumu',
            'Su Isleri Dairesi' => 'Su İşleri Dairesi',
            'Telekomunikasyon Dairesi' => 'Telekomünikasyon Dairesi',
            'Devlet Hastanesi - Lefkosa' => 'Devlet Hastanesi - Lefkoşa',
            'Devlet Hastanesi - Gazimagusa' => 'Devlet Hastanesi - Gazimağusa',
            'Devlet Hastanesi - Guzelyurt' => 'Devlet Hastanesi - Güzelyurt',
            'Devlet Hastanesi - Iskele' => 'Devlet Hastanesi - İskele',
            'Elektrik fatura ve kesinti sikayetleri' => 'Elektrik fatura ve kesinti şikâyetleri',
            'Su abonelik ve kesinti sikayetleri' => 'Su abonelik ve kesinti şikâyetleri',
            'Internet ve telefon hizmetleri' => 'İnternet ve telefon hizmetleri',
            'Toplu tasima ve durak hizmetleri' => 'Toplu taşıma ve durak hizmetleri',
            'Ozel universite ve ogrenci hizmetleri' => 'Özel üniversite ve öğrenci hizmetleri',
            'Ozel hastane ve klinik hizmetleri' => 'Özel hastane ve klinik hizmetleri',
            'Market, restoran ve tuketici sikayetleri' => 'Market, restoran ve tüketici şikâyetleri',
            'Insaat, site ve apartman yonetimi' => 'İnşaat, site ve apartman yönetimi',
        ];

        foreach ($entities as $from => $to) {
            Entity::query()->where('name', $from)->update(['name' => $to]);
        }
    }

    private function deduplicateReferenceData(): void
    {
        $duplicateRegions = Region::query()
            ->select('name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('name')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateRegions as $duplicate) {
            $ids = Region::query()
                ->where('name', $duplicate->name)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            Region::query()->whereIn('parent_id', $ids)->update(['parent_id' => $duplicate->keep_id]);
            Entity::query()->whereIn('region_id', $ids)->update(['region_id' => $duplicate->keep_id]);
            DB::table('corruption_reports')->whereIn('region_id', $ids)->update(['region_id' => $duplicate->keep_id]);
            Region::query()->whereIn('id', $ids)->delete();
        }

        $duplicateEntities = Entity::query()
            ->select('name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('name')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateEntities as $duplicate) {
            $ids = Entity::query()
                ->where('name', $duplicate->name)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            DB::table('corruption_reports')->whereIn('entity_id', $ids)->update(['entity_id' => $duplicate->keep_id]);
            Entity::query()->whereIn('id', $ids)->delete();
        }
    }
}
