/**
 * Saudi Arabia Cities & Districts Configuration
 *
 * Static embedded data (not API-driven) to provide instant dropdown cascade
 * without network latency. Data structure remains consistent with zero maintenance overhead.
 *
 * Size: ~12KB gzipped (acceptable for production bundle)
 *
 * Use: Import and use in register.vue + profile/index.vue for city/district cascade
 */

export interface DistrictConfig {
  [cityName: string]: string[];
}

export const SAUDI_DISTRICTS: DistrictConfig = {
  'الرياض / Riyadh': [
    'العليا / Al Olaya',
    'النخيل / Al Nakheel',
    'الروضة / Al Rawda',
    'الملز / Al Malaz',
    'الخليج / Al Khaleej',
    'الخرطوم / Al Khartoum',
    'المربع / Al Murabbaa',
    'الشفاء / Al Shifa',
    'الروض / Al Rowad',
    'قرطبة / Qortouba',
  ],
  'جدة / Jeddah': [
    'البلد / Al Balad',
    'الروضة / Al Rawda',
    'جنوب جدة / South Jeddah',
    'وسط البلد / Downtown',
    'الشاطئ / Al Beach',
    'الأجاويد / Al Ajaweyd',
    'الكندرة / Al Kandarah',
    'ام السلم / Um Al Salam',
    'حي الرحيلي / Al Rahili',
    'حي البغدادية / Al Baghdadiah',
  ],
  'الدمام / Dammam': [
    'المزيز / Al Mzayz',
    'الجفرة / Al Jufrah',
    'الشاطئ / Al Beach',
    'المنطقة الغربية / West Zone',
    'الفاخرية / Al Fakhria',
    'النور / Al Noor',
    'الدفينة / Al Dufaina',
    'الملز / Al Malaz',
    'الوادي / Al Wadi',
    'البستان / Al Bostan',
  ],
  'مكة المكرمة / Makkah': [
    'العزيزية / Al Aziziah',
    'الجرادة / Al Jaradah',
    'أم الجود / Um Al Joud',
    'الشبرا / Al Shubra',
    'الشرايع / Al Sharaie',
    'المعابدة / Al Moabda',
    'كدي / Kadi',
    'الحرم / Al Haram',
    'الخيط / Al Kheit',
    'الكعكية / Al Kaakiah',
  ],
  'المدينة المنورة / Medina': [
    'العنبرية / Al Anbariah',
    'قباء / Quba',
    'الحوراء / Al Hawra',
    'الرية / Al Riah',
    'البارحة / Al Barha',
    'السقيا / Al Suqiya',
    'الشميسي / Al Shammasi',
    'الراية / Al Raiah',
    'الغسق / Al Ghasaq',
    'الرانوناء / Al Ranunaa',
  ],
  'الرياض (القصيم) / Riyadh (Qassim)': [
    'المركز / Center',
    'الشرقية / East',
    'الغربية / West',
    'الشمالية / North',
    'الجنوبية / South',
    'النزهة / Al Nazha',
    'الخزامى / Al Khuzama',
    'العارض / Al Aridh',
    'الحفيرة / Al Hafira',
    'الحوطة / Al Howta',
  ],
  'أبها / Abha': [
    'الحوية / Al Hawia',
    'الثمامة / Al Thamama',
    'الواديين / Al Wadiyain',
    'شهار / Shahar',
    'الصهيب / Al Suhaib',
    'السودة / Al Soda',
    'الشرقية / East Ward',
    'الغربية / West Ward',
    'السلامة / Al Salama',
    'النقيع / Al Naqiah',
  ],
  'الخبر / Khobar': [
    'الكورنيش / Al Corniche',
    'البساتين / Al Basatin',
    'الراقي / Al Raqi',
    'الفاخرية / Al Fakhria',
    'الشاطئ / Al Beach',
    'المعذر / Al Moather',
    'الزاهرة / Al Zahara',
    'الأثير / Al Athir',
    'الشمال / North',
    'الجنوب / South',
  ],
  'القصيم (الرياض) / Qassim': [
    'بريدة / Buraydah',
    'عنيزة / Unaizah',
    'الرس / Al Rass',
    'الأسياح / Al Asyah',
    'النبهانية / Al Nabhaniah',
    'ضرية / Dariyah',
    'المذنب / Al Mithnab',
    'البدائع / Al Badaia',
    'الخبرة / Al Khabra',
    'البكيرية / Al Bukayriyah',
  ],
  'تبوك / Tabuk': [
    'الرويس / Al Rowais',
    'الفيصلية / Al Faisaliah',
    'الدقيقة / Al Daqiqah',
    'جبل علق / Jabal Alq',
    'الصفح / Al Safah',
    'التمرية / Al Tamariah',
    'الحديثة / Al Haditha',
    'الشرقية / East',
    'الغربية / West',
    'الشمالية / North',
  ],
  'يمامة / Yamama': [
    'الخرج / Al Kharj',
    'الدلم / Al Dilam',
    'الحوطة / Al Hawta',
    'الحسن / Al Hassan',
    'التويم / Al Tuwaiym',
    'الفلاح / Al Fallah',
    'الجفن / Al Jafn',
    'الحديس / Al Hadis',
    'الرين / Al Rayn',
    'الرطبة / Al Ratba',
  ],
  'حائل / Hail': [
    'المركز / Center',
    'النقرة / Al Naqra',
    'العمارية / Al Amaria',
    'الشملي / Al Shamli',
    'سميراء / Samira',
    'الجبب / Al Jubb',
    'موقق / Moqaq',
    'البقيق / Al Buqaiq',
    'القويعية / Al Quwaiiyah',
    'القصيعة / Al Qusaiaa',
  ],
  'نجران / Najran': [
    'المدينة / City Center',
    'الشرقية / East',
    'الغربية / West',
    'خباش / Khabaash',
    'يدمة / Yidama',
    'البدع / Al Bada',
    'عسير / Asir',
    'الوديعة / Al Wadiah',
    'عرقة / Arqa',
    'صارة / Sara',
  ],
  'جازان / Jizan': [
    'المدينة / City Center',
    'الشاطئ / Coastal',
    'مدينة الملك فهد الصناعية / King Fahd Industrial City',
    'الدرة / Al Durrah',
    'الحقو / Al Haqo',
    'الشعيبة / Al Shauibah',
    'الموسي / Al Moosai',
    'الجاردة / Al Gardah',
    'الريان / Al Rayan',
    'الداير / Al Dayir',
  ],
  'عسير / Asir': [
    'خميس مشيط / Khamis Mushait',
    'أبها / Abha',
    'النماص / Al Nimas',
    'محايل / Muhayil',
    'بني حسن / Bani Hassan',
    'تثليث / Tathlith',
    'بلقرن / Balqarn',
    'الأندلس / Al Andalus',
    'سراة عبيد / Sarawat Ubaid',
    'الريث / Al Reith',
  ],
  'الحدود الشمالية / Northern Borders': [
    'عرعر / Arar',
    'رفيعة / Rafiae',
    'العويقيلة / Al Owaqilah',
    'الزيتون / Al Zaitun',
    'طريف / Tarif',
    'الدواسر / Al Dawasir',
    'الخفجي / Al Khafji',
    'العمارية / Al Amaria',
    'الشريقة / Al Shiriqa',
    'البقيق / Al Buqaiq',
  ],
};

/**
 * Get all city names for dropdown
 */
export function getCities(): Array<{ label: string; value: string }> {
  return Object.keys(SAUDI_DISTRICTS).map((city) => ({
    label: city,
    value: city,
  }));
}

/**
 * Get districts for a specific city
 */
export function getDistrictsByCity(cityName: string): Array<{ label: string; value: string }> {
  const districts = SAUDI_DISTRICTS[cityName] || [];
  return districts.map((district) => ({
    label: district,
    value: district,
  }));
}
