<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'privacy_policy_en' => <<<'HTML'
<h2>Privacy Policy</h2>

<p>
At iD+ by Mhawer, we respect your privacy and are committed to protecting your
personal information. This Privacy Policy explains how we collect, use,
store, and protect information when you use our application and services.
</p>

<h3>1. Information We Collect</h3>
<p>
We may collect information that you provide directly when using our services,
including your name, email address, phone number, employee information,
business card information, and other information required to provide the
requested services.
</p>

<h3>2. How We Use Your Information</h3>
<p>
We use collected information to provide and improve our services, authenticate
users, manage user accounts, provide business card functionality, communicate
with users, and maintain the security and reliability of the application.
</p>

<h3>3. Information Sharing</h3>
<p>
We do not sell or rent your personal information. Information may be shared
with authorized service providers only when necessary to provide our services,
operate the application, or comply with applicable laws and regulations.
</p>

<h3>4. Data Security</h3>
<p>
We implement reasonable technical and organizational measures to protect your
personal information against unauthorized access, disclosure, alteration,
or destruction.
</p>

<h3>5. Data Retention</h3>
<p>
We retain personal information only for as long as necessary to provide our
services, comply with legal obligations, resolve disputes, and enforce our
agreements.
</p>

<h3>6. Your Rights</h3>
<p>
Subject to applicable laws and regulations, you may request access to,
correction of, or deletion of your personal information. You may also contact
us regarding questions or concerns about the processing of your information.
</p>

<h3>7. Children's Privacy</h3>
<p>
Our services are not directed to children under the applicable legal age.
We do not knowingly collect personal information from children without
appropriate authorization.
</p>

<h3>8. Changes to This Privacy Policy</h3>
<p>
We may update this Privacy Policy from time to time. Any changes will be
published through the application or our website.
</p>

<h3>9. Contact Us</h3>
<p>
If you have questions about this Privacy Policy or how your information is
handled, please contact us at:
</p>

<p>
<strong>Email:</strong> support@idplus.cfd<br>
<strong>Phone:</strong> +966530555410<br>
<strong>Address:</strong> Riyadh, Saudi Arabia
</p>
HTML,

            'privacy_policy_ar' => <<<'HTML'
<h2>سياسة الخصوصية</h2>

<p>
نحن في iD+ by Mhawer نحترم خصوصيتك ونلتزم بحماية معلوماتك الشخصية.
توضح سياسة الخصوصية هذه كيفية جمع معلوماتك واستخدامها وتخزينها وحمايتها
عند استخدام التطبيق والخدمات المقدمة من خلاله.
</p>

<h3>1. المعلومات التي نجمعها</h3>
<p>
قد نقوم بجمع المعلومات التي تقدمها لنا مباشرة عند استخدام خدماتنا،
بما في ذلك الاسم وعنوان البريد الإلكتروني ورقم الهاتف ومعلومات الموظف
ومعلومات بطاقة العمل وأي معلومات أخرى مطلوبة لتقديم الخدمات المطلوبة.
</p>

<h3>2. كيفية استخدام المعلومات</h3>
<p>
نستخدم المعلومات التي يتم جمعها لتقديم خدماتنا وتحسينها، والتحقق من هوية
المستخدمين، وإدارة الحسابات، وتوفير خدمات بطاقات العمل، والتواصل مع
المستخدمين، والحفاظ على أمن التطبيق وموثوقيته.
</p>

<h3>3. مشاركة المعلومات</h3>
<p>
لا نقوم ببيع أو تأجير معلوماتك الشخصية. وقد تتم مشاركة المعلومات مع
مزودي الخدمات المعتمدين فقط عندما يكون ذلك ضروريًا لتقديم الخدمات،
أو تشغيل التطبيق، أو الامتثال للأنظمة والقوانين المعمول بها.
</p>

<h3>4. حماية المعلومات</h3>
<p>
نطبق إجراءات تقنية وتنظيمية مناسبة لحماية معلوماتك الشخصية من الوصول
غير المصرح به أو الإفصاح أو التعديل أو الإتلاف.
</p>

<h3>5. الاحتفاظ بالبيانات</h3>
<p>
نحتفظ بالمعلومات الشخصية فقط للمدة اللازمة لتقديم خدماتنا، والامتثال
للالتزامات القانونية، وحل النزاعات، وتنفيذ الاتفاقيات.
</p>

<h3>6. حقوق المستخدم</h3>
<p>
وفقًا للأنظمة واللوائح المعمول بها، يمكنك طلب الوصول إلى معلوماتك الشخصية
أو تصحيحها أو حذفها. كما يمكنك التواصل معنا بشأن أي استفسارات أو مخاوف
متعلقة بكيفية معالجة معلوماتك.
</p>

<h3>7. خصوصية الأطفال</h3>
<p>
خدماتنا غير موجهة للأطفال الذين تقل أعمارهم عن السن القانوني المعمول به،
ولا نقوم بجمع معلومات شخصية من الأطفال بشكل متعمد دون الحصول على
التفويض المناسب.
</p>

<h3>8. تحديث سياسة الخصوصية</h3>
<p>
قد نقوم بتحديث سياسة الخصوصية هذه من وقت لآخر. وسيتم نشر أي تغييرات
من خلال التطبيق أو موقعنا الإلكتروني.
</p>

<h3>9. التواصل معنا</h3>
<p>
إذا كان لديك أي استفسار حول سياسة الخصوصية أو كيفية التعامل مع معلوماتك،
يمكنك التواصل معنا من خلال:
</p>

<p>
<strong>البريد الإلكتروني:</strong> support@idplus.cfd<br>
<strong>الهاتف:</strong> +966530555410<br>
<strong>العنوان:</strong> الرياض، المملكة العربية السعودية
</p>
HTML,

            'terms_conditions_en' => <<<'HTML'
<h2>Terms &amp; Conditions</h2>

<p>
These Terms &amp; Conditions govern your use of the iD+ by Mhawer application
and related services. By using the application, you agree to comply with
these terms.
</p>

<h3>1. Use of the Service</h3>
<p>
You agree to use the application only for lawful purposes and in accordance
with applicable laws, regulations, and organizational policies.
</p>

<h3>2. Account Security</h3>
<p>
You are responsible for maintaining the confidentiality of your account
credentials and for all activity performed through your account.
</p>

<h3>3. User Information</h3>
<p>
You agree to provide accurate and up-to-date information when using the
application and to update your information when necessary.
</p>

<h3>4. Business Cards</h3>
<p>
Business card information must be accurate and must not contain unlawful,
misleading, or unauthorized content. Users are responsible for information
they submit or publish through the service.
</p>

<h3>5. Prohibited Use</h3>
<p>
You must not misuse the application, attempt to gain unauthorized access,
interfere with its operation, or use the service for unlawful purposes.
</p>

<h3>6. Service Availability</h3>
<p>
We may modify, suspend, or discontinue parts of the service when necessary
for maintenance, security, upgrades, or operational requirements.
</p>

<h3>7. Changes to These Terms</h3>
<p>
We may update these Terms &amp; Conditions from time to time. Continued use
of the application after changes are published constitutes acceptance of
the updated terms.
</p>

<h3>8. Contact</h3>
<p>
For questions regarding these Terms &amp; Conditions, contact us at
support@idplus.cfd.
</p>
HTML,

            'terms_conditions_ar' => <<<'HTML'
<h2>الشروط والأحكام</h2>

<p>
تنظم هذه الشروط والأحكام استخدام تطبيق iD+ by Mhawer والخدمات المرتبطة به.
باستخدام التطبيق، فإنك توافق على الالتزام بهذه الشروط.
</p>

<h3>1. استخدام الخدمة</h3>
<p>
تتعهد باستخدام التطبيق للأغراض النظامية فقط وبما يتوافق مع الأنظمة واللوائح
والسياسات المعمول بها.
</p>

<h3>2. أمان الحساب</h3>
<p>
أنت مسؤول عن الحفاظ على سرية بيانات الدخول الخاصة بحسابك وعن جميع الأنشطة
التي تتم من خلال حسابك.
</p>

<h3>3. معلومات المستخدم</h3>
<p>
تتعهد بتقديم معلومات صحيحة ومحدثة عند استخدام التطبيق وتحديث معلوماتك
عند الحاجة.
</p>

<h3>4. بطاقات العمل</h3>
<p>
يجب أن تكون معلومات بطاقة العمل صحيحة، وألا تتضمن أي محتوى مخالف للأنظمة
أو مضلل أو غير مصرح به. يتحمل المستخدم مسؤولية المعلومات التي يقوم
بإدخالها أو نشرها من خلال الخدمة.
</p>

<h3>5. الاستخدامات المحظورة</h3>
<p>
يُمنع إساءة استخدام التطبيق أو محاولة الوصول غير المصرح به إلى أنظمته
أو تعطيل عمله أو استخدام الخدمة لأي أغراض مخالفة للأنظمة.
</p>

<h3>6. توفر الخدمة</h3>
<p>
يجوز لنا تعديل أو تعليق أو إيقاف جزء من الخدمة عند الحاجة لأغراض الصيانة
أو الأمان أو التحديثات أو المتطلبات التشغيلية.
</p>

<h3>7. تحديث الشروط والأحكام</h3>
<p>
قد نقوم بتحديث هذه الشروط والأحكام من وقت لآخر. ويُعد استمرارك في استخدام
التطبيق بعد نشر التحديثات موافقة على الشروط المحدثة.
</p>

<h3>8. التواصل</h3>
<p>
للاستفسارات المتعلقة بهذه الشروط والأحكام، يمكنك التواصل معنا عبر:
support@idplus.cfd
</p>
HTML,

            'contact_email'     => 'support@idplus.cfd',
            'contact_phone'     => '+966530555410',
            'contact_whatsapp'  => '+966530555410',
            'contact_address'   => 'Riyadh, Saudi Arabia',

            'constants_app_name'    => 'iD+ by Mhawer',
            'constants_app_version' => '1.0.0',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
