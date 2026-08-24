<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The real privacy policy and terms, replacing the seeded placeholders.
 *
 * SettingsSeeder uses firstOrCreate, so once the placeholder rows exist it can
 * never replace them — which is why https://idplus.cfd/privacy-policy still
 * reads "Placeholder privacy policy. Replace via the dashboard." Apple opens
 * that URL by hand during review, so it has to hold a real policy.
 *
 * This seeder deliberately OVERWRITES those four rows (and the placeholder
 * contact phone). Re-run it whenever the wording changes:
 *
 *   php artisan db:seed --class=LegalContentSeeder --force
 *
 * The values are HTML fragments; the public legal page renders them directly.
 */
class LegalContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->values() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('Legal content updated. Check:');
        $this->command->info('  https://idplus.cfd/privacy-policy');
        $this->command->info('  https://idplus.cfd/terms-conditions');
        $this->command->warn('contact_phone is still a placeholder if you did not edit it below.');
    }

    private function values(): array
    {
        return [
            'privacy_policy_en'   => $this->privacyEn(),
            'privacy_policy_ar'   => $this->privacyAr(),
            'terms_conditions_en' => $this->termsEn(),
            'terms_conditions_ar' => $this->termsAr(),

            // TODO(owner): replace with a number that is actually answered.
            // Apple treats an unreachable support contact as grounds for
            // rejection under Guideline 1.5.
            'contact_email'    => 'support@idplus.cfd',
            'contact_phone'    => '+966500000000',
            'contact_whatsapp' => '+966500000000',
            'contact_address'  => 'Riyadh, Saudi Arabia',
        ];
    }

    private function privacyEn(): string
    {
        return <<<'HTML'
<p><em>Last updated: 15 August 2026</em></p>

<p><strong>iD+ by Mhawer</strong> is a corporate digital business card platform. A company
subscribes, adds its employees, and each employee receives a digital business card they can
share by NFC tap, QR code or link. This policy explains what personal data we collect, why,
and what rights you have. It covers the iOS and Android apps, the web dashboard, and the
public card pages on idplus.cfd.</p>

<p><strong>Who controls your data.</strong> iD+ is a business-to-business product. If you are
an employee using iD+, your employer controls your employment data and decides what appears on
your card. We process it on your employer's instructions, as their service provider.</p>

<h2>1. Data we collect</h2>
<ul>
  <li><strong>Name, work email, phone number</strong> — to identify you, sign you in, and fill
      in your business card. Supplied by your employer, or by you.</li>
  <li><strong>Job title, department, branch, employee number</strong> — shown on your card.
      Supplied by your employer.</li>
  <li><strong>National ID / Iqama number</strong> — used by your employer as the unique
      identifier for an employee record. It is <strong>never</strong> shown on your public card
      and is never disclosed to anyone who views your card.</li>
  <li><strong>Profile photograph</strong> — shown on your card, only if you choose to add one.</li>
  <li><strong>Card design choices</strong> (colour, biography, secondary phone) — to render your card.</li>
  <li><strong>Card interaction counts</strong> (views, taps, saves) — so you and your employer can
      see how often your card is used.</li>
  <li><strong>Contact details submitted by people you meet</strong> — name, email, optional phone
      and note. They enter these themselves on your public card page and consent at the time.</li>
  <li><strong>IP address and browser user agent</strong> of anyone submitting that contact form —
      for security, abuse prevention and spam control.</li>
</ul>

<h2>2. What we do not do</h2>
<ul>
  <li>We do <strong>not</strong> sell personal data.</li>
  <li>We do <strong>not</strong> use your data for advertising. There are no advertising or
      tracking SDKs in our apps.</li>
  <li>We do <strong>not</strong> track you across other companies' apps or websites. Our iOS app
      does not access the Advertising Identifier and shows no App Tracking Transparency prompt.</li>
  <li>We do <strong>not</strong> publish your national ID / Iqama number.</li>
  <li>We use <strong>no</strong> artificial intelligence or machine-learning service.</li>
</ul>

<h2>3. What is public</h2>
<p>A card becomes reachable at a public link only after your employer approves and publishes it.
A published card shows the details intended for professional sharing: your name, job title,
employer, work contact details, photograph, biography and any links you added. Anyone with the
link, the QR code or an NFC tap can view it. You may ask your employer to unpublish it at any
time.</p>

<h2>4. Device permissions</h2>
<ul>
  <li><strong>Photo library</strong> — only if you choose an existing photo for your card.</li>
  <li><strong>Camera</strong> — only if you choose to take a new photo for your card.</li>
  <li><strong>NFC</strong> — to write your card to a physical NFC tag. Used only while the
      sharing screen is open.</li>
</ul>
<p>Each permission is requested only when you use that feature, and the app works without
granting them — you simply cannot use that particular feature.</p>

<h2>5. Service providers</h2>
<ul>
  <li><strong>Our hosting provider</strong> — stores the application database and uploaded images.</li>
  <li><strong>OurSMS</strong> (Saudi Arabia) — delivers one-time passcodes by SMS for optional
      phone verification and password resets. It receives a phone number and the message text.</li>
  <li><strong>Google Fonts</strong> — our apps and website load their typeface from Google's font
      CDN. No personal data is sent; the request carries only the font name and, unavoidably,
      your IP address.</li>
  <li><strong>Email delivery (SMTP)</strong> — for password resets and account credentials.</li>
</ul>
<p>We use no analytics, advertising, payment or AI service.</p>

<h2>6. How long we keep data</h2>
<p>Employment and card data is kept while your employer's account is active and you remain an
employee on it. Deleted records are first marked deleted and then permanently removed within 90
days. Contact details shared with you are kept until you delete them or your account is closed.</p>

<h2>7. Your rights</h2>
<p>You may request access to, correction of, or deletion of your personal data, and you may
object to processing. Because your employer controls your employment record, the fastest route
is usually your company administrator, who can remove your account and card immediately. You may
also email us and we will respond within 30 days.</p>

<h2>8. Accounts and deletion</h2>
<p>iD+ does not offer public sign-up. Accounts are created only by your employer's administrator.
To have your account and its data deleted, ask your company administrator, or email
<a href="mailto:support@idplus.cfd">support@idplus.cfd</a> and we will action it.</p>

<h2>9. Children</h2>
<p>iD+ is a workplace tool for adults in employment. It is not directed at children and we do not
knowingly collect data from anyone under 16.</p>

<h2>10. Security</h2>
<p>All traffic between our apps and our servers uses HTTPS. Passwords are stored hashed, never in
plain text. Each company's data is accessible only to that company's own users.</p>

<h2>11. Changes</h2>
<p>If we change this policy we will update the date above, and notify you in the app for
significant changes.</p>

<h2>12. Contact</h2>
<p>Email: <a href="mailto:support@idplus.cfd">support@idplus.cfd</a><br>
Riyadh, Kingdom of Saudi Arabia</p>
<p>This policy is governed by the laws of the Kingdom of Saudi Arabia.</p>
HTML;
    }

    private function privacyAr(): string
    {
        return <<<'HTML'
<p><em>آخر تحديث: ١٥ أغسطس ٢٠٢٦</em></p>

<p><strong>‎iD+ من Mhawer</strong> منصة بطاقات عمل رقمية للشركات. تشترك المنشأة، وتضيف موظفيها،
ويحصل كل موظف على بطاقة عمل رقمية يشاركها عبر لمسة NFC أو رمز QR أو رابط. توضّح هذه السياسة
البيانات الشخصية التي نجمعها وسببها وحقوقك تجاهها، وتشمل تطبيقَي iOS وAndroid ولوحة التحكم
وصفحات البطاقات العامة على idplus.cfd.</p>

<p><strong>من يتحكم في بياناتك.</strong> ‎iD+ منتج موجّه للشركات. إذا كنت موظفًا فإن جهة عملك هي
المتحكم في بيانات التوظيف الخاصة بك وهي التي تحدد ما يظهر على بطاقتك، ونحن نعالجها بناءً على
تعليماتها بصفتنا مزوّد خدمة لها.</p>

<h2>١. البيانات التي نجمعها</h2>
<ul>
  <li><strong>الاسم والبريد الإلكتروني للعمل ورقم الجوال</strong> — للتعريف بك وتسجيل دخولك
      وتعبئة بطاقتك.</li>
  <li><strong>المسمى الوظيفي والقسم والفرع والرقم الوظيفي</strong> — تظهر على بطاقتك، وتوفرها جهة عملك.</li>
  <li><strong>رقم الهوية أو الإقامة</strong> — تستخدمه جهة عملك كمعرّف فريد لسجل الموظف،
      و<strong>لا يظهر إطلاقًا</strong> على بطاقتك العامة ولا يُفصح عنه لمن يطّلع عليها.</li>
  <li><strong>الصورة الشخصية</strong> — تظهر على بطاقتك فقط إن اخترت إضافتها.</li>
  <li><strong>خيارات تصميم البطاقة</strong> (اللون والنبذة ورقم جوال إضافي) — لعرض بطاقتك.</li>
  <li><strong>إحصاءات التفاعل</strong> (المشاهدات واللمسات والحفظ) — لعرض عدد مرات استخدام بطاقتك.</li>
  <li><strong>بيانات التواصل التي يرسلها من تقابلهم</strong> — الاسم والبريد ورقم الجوال وملاحظة
      اختيارية، يدخلها الشخص بنفسه ويوافق عليها وقت الإرسال.</li>
  <li><strong>عنوان IP ومعرّف المتصفح</strong> لمن يرسل ذلك النموذج — للأمان ومنع إساءة الاستخدام.</li>
</ul>

<h2>٢. ما لا نقوم به</h2>
<ul>
  <li><strong>لا</strong> نبيع البيانات الشخصية.</li>
  <li><strong>لا</strong> نستخدم بياناتك في الإعلانات، ولا توجد أدوات تتبع إعلاني في تطبيقاتنا.</li>
  <li><strong>لا</strong> نتتبعك عبر تطبيقات أو مواقع شركات أخرى، ولا يصل تطبيقنا إلى معرّف
      المعلنين، ولذلك لا يعرض نافذة «تتبع التطبيقات».</li>
  <li><strong>لا</strong> ننشر رقم الهوية أو الإقامة.</li>
  <li><strong>لا</strong> نستخدم أي خدمة ذكاء اصطناعي أو تعلّم آلي.</li>
</ul>

<h2>٣. ما هو علني</h2>
<p>لا تصبح البطاقة متاحة على رابط عام إلا بعد اعتماد جهة عملك لها ونشرها. وتعرض البطاقة المنشورة
البيانات المخصصة للمشاركة المهنية: الاسم والمسمى الوظيفي وجهة العمل وبيانات التواصل والصورة
والنبذة والروابط التي أضفتها. ويمكن لأي شخص لديه الرابط أو رمز QR أو لمسة NFC الاطلاع عليها،
ويمكنك طلب إلغاء نشرها في أي وقت.</p>

<h2>٤. أذونات الجهاز</h2>
<ul>
  <li><strong>مكتبة الصور</strong> — فقط إن اخترت صورة موجودة لبطاقتك.</li>
  <li><strong>الكاميرا</strong> — فقط إن اخترت التقاط صورة جديدة.</li>
  <li><strong>NFC</strong> — لكتابة بطاقتك على بطاقة NFC مادية، ويُستخدم فقط أثناء فتح شاشة المشاركة.</li>
</ul>
<p>يُطلب كل إذن عند استخدام الميزة فقط، ويعمل التطبيق بدونها غير أنك لن تتمكن من استخدام تلك
الميزة تحديدًا.</p>

<h2>٥. مزودو الخدمة</h2>
<ul>
  <li><strong>مزود الاستضافة</strong> — يخزّن قاعدة البيانات والصور المرفوعة.</li>
  <li><strong>OurSMS</strong> (السعودية) — لإرسال رموز التحقق لمرة واحدة عبر الرسائل النصية.</li>
  <li><strong>Google Fonts</strong> — تُحمّل خطوط تطبيقاتنا وموقعنا من شبكة خطوط Google، ولا
      تُرسل بيانات شخصية سوى عنوان IP بحكم طبيعة الاتصال.</li>
  <li><strong>البريد الإلكتروني (SMTP)</strong> — لإعادة تعيين كلمات المرور وإرسال بيانات الدخول.</li>
</ul>
<p>لا نستخدم أي خدمة تحليلات أو إعلانات أو مدفوعات أو ذكاء اصطناعي.</p>

<h2>٦. مدة الاحتفاظ</h2>
<p>يُحتفظ ببيانات التوظيف والبطاقة طالما ظل حساب جهة عملك نشطًا وظللت موظفًا لديها. وتُزال
السجلات المحذوفة نهائيًا خلال ٩٠ يومًا. أما بيانات التواصل المشاركة معك فتبقى حتى تحذفها أو
يُغلق حسابك.</p>

<h2>٧. حقوقك</h2>
<p>يحق لك طلب الاطلاع على بياناتك أو تصحيحها أو حذفها والاعتراض على معالجتها. ولأن جهة عملك هي
المتحكمة في سجلك الوظيفي فإن أسرع طريق عادةً هو مسؤول الشركة لديك، ويمكنه حذف حسابك وبطاقتك
فورًا. ويمكنك أيضًا مراسلتنا وسنرد خلال ٣٠ يومًا.</p>

<h2>٨. الحسابات والحذف</h2>
<p>لا يوفر ‎iD+ تسجيلًا ذاتيًا؛ تُنشأ الحسابات من مسؤول جهة عملك فقط. ولحذف حسابك وبياناته
تواصل مع مسؤول الشركة أو راسلنا على
<a href="mailto:support@idplus.cfd">support@idplus.cfd</a>.</p>

<h2>٩. الأطفال</h2>
<p>‎iD+ أداة عمل للبالغين العاملين، وليست موجهة للأطفال، ولا نجمع عن قصد بيانات من هم دون
السادسة عشرة.</p>

<h2>١٠. الأمان</h2>
<p>تُنقل جميع البيانات عبر HTTPS، وتُخزَّن كلمات المرور مشفّرة لا كنص صريح، والوصول إلى بيانات
كل شركة مقصور على مستخدميها.</p>

<h2>١١. التغييرات</h2>
<p>عند تعديل هذه السياسة سنحدّث التاريخ أعلاه، وسنُعلمك داخل التطبيق بالتغييرات الجوهرية.</p>

<h2>١٢. التواصل</h2>
<p>البريد: <a href="mailto:support@idplus.cfd">support@idplus.cfd</a><br>
الرياض، المملكة العربية السعودية</p>
<p>تخضع هذه السياسة لأنظمة المملكة العربية السعودية.</p>
HTML;
    }

    private function termsEn(): string
    {
        return <<<'HTML'
<p><em>Last updated: 15 August 2026</em></p>

<h2>1. What iD+ is</h2>
<p>iD+ by Mhawer provides digital business cards to companies and their employees. Access is
granted to a company under a commercial agreement; individual employees receive their accounts
from their employer.</p>

<h2>2. Accounts</h2>
<p>Accounts are created by your employer's administrator. You are responsible for keeping your
password confidential and for activity carried out under your account. Tell us promptly if you
believe your account has been used without your permission.</p>

<h2>3. Your card content</h2>
<p>You may personalise your card with a photograph, a biography, a secondary phone number and
colours. Your employer reviews and approves what is published. You must not upload content that
is unlawful, misleading, offensive, or that infringes anyone's rights, and you must have the
right to use any image you upload. We may remove content that breaches these terms.</p>

<h2>4. Public card pages</h2>
<p>A published card is reachable by anyone holding its link, QR code or NFC tag. Do not put
information on your card that you are not willing to share publicly.</p>

<h2>5. Contact details sent to you</h2>
<p>People who view your card may send you their own contact details. Use them only for legitimate
professional purposes and in accordance with applicable data protection law.</p>

<h2>6. Acceptable use</h2>
<p>Do not attempt to gain unauthorised access to the service, disrupt it, scrape it, or use it to
send unsolicited messages.</p>

<h2>7. Availability</h2>
<p>We aim to keep the service available but do not guarantee uninterrupted operation. We may
change or suspend features, and we will give reasonable notice of material changes where we can.</p>

<h2>8. Termination</h2>
<p>Your employer may deactivate your account and unpublish your card at any time. We may suspend
accounts that breach these terms.</p>

<h2>9. Liability</h2>
<p>To the extent permitted by law, we are not liable for indirect or consequential loss arising
from use of the service.</p>

<h2>10. Governing law</h2>
<p>These terms are governed by the laws of the Kingdom of Saudi Arabia.</p>

<h2>11. Contact</h2>
<p>Email: <a href="mailto:support@idplus.cfd">support@idplus.cfd</a></p>
HTML;
    }

    private function termsAr(): string
    {
        return <<<'HTML'
<p><em>آخر تحديث: ١٥ أغسطس ٢٠٢٦</em></p>

<h2>١. ما هو ‎iD+</h2>
<p>يوفّر ‎iD+ من Mhawer بطاقات عمل رقمية للشركات وموظفيها. ويُمنح الوصول للشركة بموجب اتفاقية
تجارية، ويحصل الموظفون على حساباتهم من جهة عملهم.</p>

<h2>٢. الحسابات</h2>
<p>تُنشأ الحسابات من مسؤول جهة عملك. وأنت مسؤول عن الحفاظ على سرية كلمة المرور وعن النشاط الذي
يتم عبر حسابك. أبلغنا فورًا إذا اعتقدت أن حسابك استُخدم دون إذنك.</p>

<h2>٣. محتوى بطاقتك</h2>
<p>يمكنك تخصيص بطاقتك بصورة ونبذة ورقم جوال إضافي وألوان، وتراجع جهة عملك ما يُنشر وتعتمده. ولا
يجوز رفع محتوى مخالف للأنظمة أو مضلل أو مسيء أو ينتهك حقوق الغير، ويجب أن تملك حق استخدام أي
صورة ترفعها. ويحق لنا إزالة أي محتوى يخالف هذه الشروط.</p>

<h2>٤. صفحات البطاقات العامة</h2>
<p>البطاقة المنشورة متاحة لكل من يملك رابطها أو رمز QR أو بطاقة NFC الخاصة بها، فلا تضع عليها
معلومات لا ترغب في مشاركتها علنًا.</p>

<h2>٥. بيانات التواصل المرسلة إليك</h2>
<p>قد يرسل إليك من يطّلع على بطاقتك بياناته، ويجب استخدامها لأغراض مهنية مشروعة فقط ووفقًا
لأنظمة حماية البيانات المعمول بها.</p>

<h2>٦. الاستخدام المقبول</h2>
<p>يُمنع محاولة الوصول غير المصرح به للخدمة أو تعطيلها أو استخراج بياناتها آليًا أو استخدامها
لإرسال رسائل غير مرغوب فيها.</p>

<h2>٧. الإتاحة</h2>
<p>نسعى لإبقاء الخدمة متاحة دون أن نضمن استمرارها بلا انقطاع، ويجوز لنا تعديل الميزات أو
إيقافها مع إشعار معقول بالتغييرات الجوهرية متى أمكن.</p>

<h2>٨. إنهاء الخدمة</h2>
<p>يجوز لجهة عملك تعطيل حسابك وإلغاء نشر بطاقتك في أي وقت، ويجوز لنا تعليق الحسابات المخالفة
لهذه الشروط.</p>

<h2>٩. المسؤولية</h2>
<p>في حدود ما يسمح به النظام، لا نتحمل المسؤولية عن أي خسائر غير مباشرة أو تبعية ناشئة عن
استخدام الخدمة.</p>

<h2>١٠. النظام الواجب التطبيق</h2>
<p>تخضع هذه الشروط لأنظمة المملكة العربية السعودية.</p>

<h2>١١. التواصل</h2>
<p>البريد: <a href="mailto:support@idplus.cfd">support@idplus.cfd</a></p>
HTML;
    }
}
