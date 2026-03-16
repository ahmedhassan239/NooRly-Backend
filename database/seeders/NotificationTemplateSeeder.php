<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->templates();

        foreach ($templates as $template) {
            DB::table('notification_templates')->updateOrInsert(
                ['key' => $template['key'], 'locale' => $template['locale']],
                array_merge($template, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }

    private function templates(): array
    {
        return [

            // ============================================================
            // A. PRAYER REMINDERS
            // ============================================================

            ['key' => 'prayer_fajr', 'category' => 'prayer', 'sub_type' => 'fajr',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🌅 It\'s Time for Fajr Prayer',
                'body'  => '"Prayer is better than sleep"',
                'cta'   => 'View Prayer Times', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'prayer_fajr', 'category' => 'prayer', 'sub_type' => 'fajr',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🌅 حان وقت صلاة الفجر',
                'body'  => '"الصلاة خير من النوم"',
                'cta'   => 'عرض أوقات الصلاة', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'prayer_dhuhr', 'category' => 'prayer', 'sub_type' => 'dhuhr',
                'locale' => 'en', 'priority' => 'high',
                'title' => '☀️ It\'s Time for Dhuhr Prayer',
                'body'  => 'Don\'t forget your Dhuhr prayer today',
                'cta'   => 'View Prayer Times', 'variables' => null, 'variation_group' => null, 'sort_order' => 2],

            ['key' => 'prayer_dhuhr', 'category' => 'prayer', 'sub_type' => 'dhuhr',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '☀️ حان وقت صلاة الظهر',
                'body'  => 'لا تنسَ صلاة الظهر اليوم',
                'cta'   => 'عرض أوقات الصلاة', 'variables' => null, 'variation_group' => null, 'sort_order' => 2],

            ['key' => 'prayer_asr', 'category' => 'prayer', 'sub_type' => 'asr',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🌤️ It\'s Time for Asr Prayer',
                'body'  => 'Asr time has entered, make wudu and pray',
                'cta'   => 'View Prayer Times', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

            ['key' => 'prayer_asr', 'category' => 'prayer', 'sub_type' => 'asr',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🌤️ حان وقت صلاة العصر',
                'body'  => 'وقت العصر دخل، توضأ وصلِّ',
                'cta'   => 'عرض أوقات الصلاة', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

            ['key' => 'prayer_maghrib', 'category' => 'prayer', 'sub_type' => 'maghrib',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🌆 It\'s Time for Maghrib Prayer',
                'body'  => 'Maghrib Adhan is now',
                'cta'   => 'View Prayer Times', 'variables' => null, 'variation_group' => null, 'sort_order' => 4],

            ['key' => 'prayer_maghrib', 'category' => 'prayer', 'sub_type' => 'maghrib',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🌆 حان وقت صلاة المغرب',
                'body'  => 'أذان المغرب الآن',
                'cta'   => 'عرض أوقات الصلاة', 'variables' => null, 'variation_group' => null, 'sort_order' => 4],

            ['key' => 'prayer_isha', 'category' => 'prayer', 'sub_type' => 'isha',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🌙 It\'s Time for Isha Prayer',
                'body'  => 'Pray Isha before sleep',
                'cta'   => 'View Prayer Times', 'variables' => null, 'variation_group' => null, 'sort_order' => 5],

            ['key' => 'prayer_isha', 'category' => 'prayer', 'sub_type' => 'isha',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🌙 حان وقت صلاة العشاء',
                'body'  => 'صلِّ العشاء قبل النوم',
                'cta'   => 'عرض أوقات الصلاة', 'variables' => null, 'variation_group' => null, 'sort_order' => 5],

            // ============================================================
            // B. LESSON REMINDERS — 5 variations morning
            // ============================================================

            ['key' => 'lesson_morning_v1', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'en', 'priority' => 'high',
                'title' => '📖 Today\'s Lesson is Ready!',
                'body'  => "Day {day_number}: {lesson_title_en}\n⏱️ Just {duration} minutes",
                'cta'   => 'Start Lesson', 'variables' => json_encode(['day_number', 'lesson_title_en', 'duration']),
                'variation_group' => 'lesson_morning', 'sort_order' => 1],

            ['key' => 'lesson_morning_v1', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '📖 درس اليوم جاهز!',
                'body'  => "اليوم {day_number}: {lesson_title_ar}\n⏱️ {duration} دقائق فقط",
                'cta'   => 'ابدأ الدرس', 'variables' => json_encode(['day_number', 'lesson_title_ar', 'duration']),
                'variation_group' => 'lesson_morning', 'sort_order' => 1],

            ['key' => 'lesson_morning_v2', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🌟 Time to Learn!',
                'body'  => "A new lesson awaits you\nDay {day_number}: {lesson_title_en}",
                'cta'   => 'Start Lesson', 'variables' => json_encode(['day_number', 'lesson_title_en']),
                'variation_group' => 'lesson_morning', 'sort_order' => 2],

            ['key' => 'lesson_morning_v2', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🌟 وقت التعلم!',
                'body'  => "درس جديد في انتظارك\nاليوم {day_number}: {lesson_title_ar}",
                'cta'   => 'ابدأ الدرس', 'variables' => json_encode(['day_number', 'lesson_title_ar']),
                'variation_group' => 'lesson_morning', 'sort_order' => 2],

            ['key' => 'lesson_morning_v3', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'en', 'priority' => 'high',
                'title' => '💚 Keep Growing',
                'body'  => "Today's lesson: {lesson_title_en}\n⏱️ Just {duration} minutes",
                'cta'   => 'Open Lesson', 'variables' => json_encode(['lesson_title_en', 'duration']),
                'variation_group' => 'lesson_morning', 'sort_order' => 3],

            ['key' => 'lesson_morning_v3', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '💚 استمر في النمو',
                'body'  => "درس اليوم: {lesson_title_ar}\n⏱️ {duration} دقائق فقط",
                'cta'   => 'فتح الدرس', 'variables' => json_encode(['lesson_title_ar', 'duration']),
                'variation_group' => 'lesson_morning', 'sort_order' => 3],

            ['key' => 'lesson_morning_v4', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'en', 'priority' => 'high',
                'title' => '📚 Your Daily Lesson',
                'body'  => "Day {day_number} of 60\nTopic: {lesson_title_en}",
                'cta'   => 'Read Now', 'variables' => json_encode(['day_number', 'lesson_title_en']),
                'variation_group' => 'lesson_morning', 'sort_order' => 4],

            ['key' => 'lesson_morning_v4', 'category' => 'lesson', 'sub_type' => 'lesson_morning',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '📚 درسك اليومي',
                'body'  => "اليوم {day_number} من 60\nالموضوع: {lesson_title_ar}",
                'cta'   => 'اقرأ الآن', 'variables' => json_encode(['day_number', 'lesson_title_ar']),
                'variation_group' => 'lesson_morning', 'sort_order' => 4],

            // Evening incomplete
            ['key' => 'lesson_evening_incomplete', 'category' => 'lesson', 'sub_type' => 'lesson_evening',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '⏰ You Haven\'t Completed Today\'s Lesson Yet',
                'body'  => "You still have time!\nDay {day_number}: {lesson_title_en}",
                'cta'   => 'Complete Now', 'variables' => json_encode(['day_number', 'lesson_title_en']),
                'variation_group' => null, 'sort_order' => 1],

            ['key' => 'lesson_evening_incomplete', 'category' => 'lesson', 'sub_type' => 'lesson_evening',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '⏰ لم تنهِ درس اليوم بعد',
                'body'  => "لا يزال لديك وقت!\nاليوم {day_number}: {lesson_title_ar}",
                'cta'   => 'أكمل الآن', 'variables' => json_encode(['day_number', 'lesson_title_ar']),
                'variation_group' => null, 'sort_order' => 1],

            // Streak reminder
            ['key' => 'lesson_streak', 'category' => 'lesson', 'sub_type' => 'streak_reminder',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '🔥 Keep Your Streak Going!',
                'body'  => "You're on a {streak_days}-day streak\nDon't break it today!",
                'cta'   => 'Continue Journey', 'variables' => json_encode(['streak_days']),
                'variation_group' => null, 'sort_order' => 1],

            ['key' => 'lesson_streak', 'category' => 'lesson', 'sub_type' => 'streak_reminder',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '🔥 استمر في التقدم!',
                'body'  => "أنت على {streak_days} يوم متتالي\nلا تقطع سلسلتك اليوم!",
                'cta'   => 'متابعة الرحلة', 'variables' => json_encode(['streak_days']),
                'variation_group' => null, 'sort_order' => 1],

            // ============================================================
            // C. DHIKR REMINDERS
            // ============================================================

            ['key' => 'dhikr_morning', 'category' => 'dhikr', 'sub_type' => 'morning_adhkar',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '☀️ Morning Adhkar',
                'body'  => "Don't forget your morning remembrances\nTap to read now",
                'cta'   => 'Read Adhkar', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'dhikr_morning', 'category' => 'dhikr', 'sub_type' => 'morning_adhkar',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '☀️ أذكار الصباح',
                'body'  => "لا تنسَ أذكار الصباح اليوم\nاضغط للقراءة الآن",
                'cta'   => 'قراءة الأذكار', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'dhikr_evening', 'category' => 'dhikr', 'sub_type' => 'evening_adhkar',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '🌙 Evening Adhkar',
                'body'  => "It's time for evening remembrances\nTap to read now",
                'cta'   => 'Read Adhkar', 'variables' => null, 'variation_group' => null, 'sort_order' => 2],

            ['key' => 'dhikr_evening', 'category' => 'dhikr', 'sub_type' => 'evening_adhkar',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '🌙 أذكار المساء',
                'body'  => "حان وقت أذكار المساء\nاضغط للقراءة الآن",
                'cta'   => 'قراءة الأذكار', 'variables' => null, 'variation_group' => null, 'sort_order' => 2],

            ['key' => 'dhikr_sleep', 'category' => 'dhikr', 'sub_type' => 'sleep_adhkar',
                'locale' => 'en', 'priority' => 'low',
                'title' => '😴 Sleep Adhkar',
                'body'  => "Don't sleep before reading sleep remembrances\nTap to read now",
                'cta'   => 'Read Adhkar', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

            ['key' => 'dhikr_sleep', 'category' => 'dhikr', 'sub_type' => 'sleep_adhkar',
                'locale' => 'ar', 'priority' => 'low',
                'title' => '😴 أذكار النوم',
                'body'  => "لا تنم قبل قراءة أذكار النوم\nاضغط للقراءة الآن",
                'cta'   => 'قراءة الأذكار', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

            // Random dhikr — 3 variations
            ['key' => 'dhikr_random_v1', 'category' => 'dhikr', 'sub_type' => 'random_dhikr',
                'locale' => 'en', 'priority' => 'low',
                'title' => '💚 Spiritual Reminder',
                'body'  => "Glory is to Allah and praise is to Him\nSubhan Allahi wa bihamdihi",
                'cta'   => null, 'variables' => null, 'variation_group' => 'random_dhikr', 'sort_order' => 1],

            ['key' => 'dhikr_random_v1', 'category' => 'dhikr', 'sub_type' => 'random_dhikr',
                'locale' => 'ar', 'priority' => 'low',
                'title' => '💚 تذكير روحي',
                'body'  => "سُبْحَانَ اللهِ وَبِحَمْدِهِ\nSubhan Allahi wa bihamdihi",
                'cta'   => null, 'variables' => null, 'variation_group' => 'random_dhikr', 'sort_order' => 1],

            ['key' => 'dhikr_random_v2', 'category' => 'dhikr', 'sub_type' => 'random_dhikr',
                'locale' => 'en', 'priority' => 'low',
                'title' => '💚 Remember Allah',
                'body'  => "There is no power nor strength except with Allah\nLa hawla wala quwwata illa billah",
                'cta'   => null, 'variables' => null, 'variation_group' => 'random_dhikr', 'sort_order' => 2],

            ['key' => 'dhikr_random_v2', 'category' => 'dhikr', 'sub_type' => 'random_dhikr',
                'locale' => 'ar', 'priority' => 'low',
                'title' => '💚 اذكر الله',
                'body'  => "لَا حَوْلَ وَلَا قُوَّةَ إِلَّا بِاللهِ\nLa hawla wala quwwata illa billah",
                'cta'   => null, 'variables' => null, 'variation_group' => 'random_dhikr', 'sort_order' => 2],

            ['key' => 'dhikr_random_v3', 'category' => 'dhikr', 'sub_type' => 'random_dhikr',
                'locale' => 'en', 'priority' => 'low',
                'title' => '💚 Spiritual Reminder',
                'body'  => "Allah is the Greatest\nAllahu Akbar",
                'cta'   => null, 'variables' => null, 'variation_group' => 'random_dhikr', 'sort_order' => 3],

            ['key' => 'dhikr_random_v3', 'category' => 'dhikr', 'sub_type' => 'random_dhikr',
                'locale' => 'ar', 'priority' => 'low',
                'title' => '💚 تذكير روحي',
                'body'  => "اللهُ أَكْبَر\nAllahu Akbar",
                'cta'   => null, 'variables' => null, 'variation_group' => 'random_dhikr', 'sort_order' => 3],

            // ============================================================
            // D. MILESTONES & ACHIEVEMENTS
            // ============================================================

            ['key' => 'milestone_day_complete', 'category' => 'milestone', 'sub_type' => 'milestone_day_complete',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '✅ Well Done!',
                'body'  => "You completed Day {day_number}\n{days_remaining} days remaining",
                'cta'   => 'Continue', 'variables' => json_encode(['day_number', 'days_remaining']),
                'variation_group' => null, 'sort_order' => 1],

            ['key' => 'milestone_day_complete', 'category' => 'milestone', 'sub_type' => 'milestone_day_complete',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '✅ أحسنت!',
                'body'  => "أكملت اليوم {day_number}\n{days_remaining} يوم متبقي",
                'cta'   => 'متابعة', 'variables' => json_encode(['day_number', 'days_remaining']),
                'variation_group' => null, 'sort_order' => 1],

            ['key' => 'milestone_week_complete', 'category' => 'milestone', 'sub_type' => 'milestone_week_complete',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '🎊 Full Week Complete!',
                'body'  => "You finished Week {week_number}\nKeep up the great work! 🔥",
                'cta'   => 'View Progress', 'variables' => json_encode(['week_number']),
                'variation_group' => null, 'sort_order' => 2],

            ['key' => 'milestone_week_complete', 'category' => 'milestone', 'sub_type' => 'milestone_week_complete',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '🎊 أسبوع كامل!',
                'body'  => "أنهيت الأسبوع {week_number}\nاستمر على هذا المنوال! 🔥",
                'cta'   => 'عرض التقدم', 'variables' => json_encode(['week_number']),
                'variation_group' => null, 'sort_order' => 2],

            ['key' => 'milestone_30_days', 'category' => 'milestone', 'sub_type' => 'milestone_30_days',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🏆 Amazing Achievement!',
                'body'  => "You completed 30 consecutive days\nHalfway there! Keep going! 💪",
                'cta'   => 'View Journey', 'variables' => null,
                'variation_group' => null, 'sort_order' => 3],

            ['key' => 'milestone_30_days', 'category' => 'milestone', 'sub_type' => 'milestone_30_days',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🏆 إنجاز عظيم!',
                'body'  => "أكملت 30 يومًا متواصلة\nنصف الطريق! واصل! 💪",
                'cta'   => 'عرض الرحلة', 'variables' => null,
                'variation_group' => null, 'sort_order' => 3],

            ['key' => 'milestone_journey_complete', 'category' => 'milestone', 'sub_type' => 'milestone_journey_complete',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🎉 Congratulations!',
                'body'  => "You completed the 60-day journey\nMay Allah bless you! 💚\nNow begin your real journey with Allah",
                'cta'   => 'View Achievement', 'variables' => null,
                'variation_group' => null, 'sort_order' => 4],

            ['key' => 'milestone_journey_complete', 'category' => 'milestone', 'sub_type' => 'milestone_journey_complete',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🎉 تهانينا!',
                'body'  => "أكملت رحلة الـ 60 يومًا\nبارك الله فيك! 💚\nالآن ابدأ رحلتك الحقيقية مع الله",
                'cta'   => 'عرض الإنجاز', 'variables' => null,
                'variation_group' => null, 'sort_order' => 4],

            ['key' => 'milestone_prayer_streak', 'category' => 'milestone', 'sub_type' => 'prayer_streak',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '📿 Masha Allah!',
                'body'  => "You prayed {prayers_count} consecutive prayers\nMay Allah keep you steadfast! 🤲",
                'cta'   => null, 'variables' => json_encode(['prayers_count']),
                'variation_group' => null, 'sort_order' => 5],

            ['key' => 'milestone_prayer_streak', 'category' => 'milestone', 'sub_type' => 'prayer_streak',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '📿 ما شاء الله!',
                'body'  => "صليت {prayers_count} صلاة متتالية\nالله يثبتك! 🤲",
                'cta'   => null, 'variables' => json_encode(['prayers_count']),
                'variation_group' => null, 'sort_order' => 5],

            // ============================================================
            // E. SPECIAL OCCASIONS
            // ============================================================

            ['key' => 'occasion_friday', 'category' => 'occasion', 'sub_type' => 'friday',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '🕌 Blessed Friday',
                'body'  => "Don't forget Jumu'ah prayer today\nRead Surah Al-Kahf 📖",
                'cta'   => 'Open App', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'occasion_friday', 'category' => 'occasion', 'sub_type' => 'friday',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '🕌 الجمعة المباركة',
                'body'  => "لا تنسَ صلاة الجمعة اليوم\nاقرأ سورة الكهف 📖",
                'cta'   => 'فتح التطبيق', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'occasion_ramadan_approaching', 'category' => 'occasion', 'sub_type' => 'ramadan_approaching',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '🌙 Ramadan is Coming!',
                'body'  => "Ramadan starts in {days_left} days\nTap to prepare yourself",
                'cta'   => 'Ramadan Guide', 'variables' => json_encode(['days_left']),
                'variation_group' => null, 'sort_order' => 2],

            ['key' => 'occasion_ramadan_approaching', 'category' => 'occasion', 'sub_type' => 'ramadan_approaching',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '🌙 رمضان قريب!',
                'body'  => "رمضان سيبدأ خلال {days_left} يوم\nاضغط لتحضير نفسك",
                'cta'   => 'دليل رمضان', 'variables' => json_encode(['days_left']),
                'variation_group' => null, 'sort_order' => 2],

            ['key' => 'occasion_laylatul_qadr', 'category' => 'occasion', 'sub_type' => 'laylatul_qadr',
                'locale' => 'en', 'priority' => 'high',
                'title' => '✨ Laylatul Qadr!',
                'body'  => "Tonight might be the Night of Power\nIncrease your dua and dhikr 🤲",
                'cta'   => 'Open App', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

            ['key' => 'occasion_laylatul_qadr', 'category' => 'occasion', 'sub_type' => 'laylatul_qadr',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '✨ ليلة القدر!',
                'body'  => "الليلة قد تكون ليلة القدر\nأكثر من الدعاء والذكر 🤲",
                'cta'   => 'فتح التطبيق', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

            ['key' => 'occasion_eid', 'category' => 'occasion', 'sub_type' => 'eid',
                'locale' => 'en', 'priority' => 'high',
                'title' => '🎉 Eid Mubarak!',
                'body'  => "May Allah accept from us and you\nHappy Eid! 💚",
                'cta'   => 'Open App', 'variables' => null, 'variation_group' => null, 'sort_order' => 4],

            ['key' => 'occasion_eid', 'category' => 'occasion', 'sub_type' => 'eid',
                'locale' => 'ar', 'priority' => 'high',
                'title' => '🎉 عيد مبارك!',
                'body'  => "تقبل الله منا ومنكم\nكل عام وأنتم بخير 💚",
                'cta'   => 'فتح التطبيق', 'variables' => null, 'variation_group' => null, 'sort_order' => 4],

            // ============================================================
            // F. HELP & SUPPORT
            // ============================================================

            ['key' => 'support_inactive_3_days', 'category' => 'support', 'sub_type' => 'inactive_3_days',
                'locale' => 'en', 'priority' => 'low',
                'title' => '💔 We Miss You!',
                'body'  => "We haven't seen you for 3 days\nIs everything okay?",
                'cta'   => 'Come Back', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'support_inactive_3_days', 'category' => 'support', 'sub_type' => 'inactive_3_days',
                'locale' => 'ar', 'priority' => 'low',
                'title' => '💔 نفتقدك!',
                'body'  => "لم نرك منذ 3 أيام\nهل كل شيء بخير؟",
                'cta'   => 'عد إلينا', 'variables' => null, 'variation_group' => null, 'sort_order' => 1],

            ['key' => 'support_inactive_7_days', 'category' => 'support', 'sub_type' => 'inactive_7_days',
                'locale' => 'en', 'priority' => 'medium',
                'title' => '🤲 We\'re Here for You',
                'body'  => "If you're struggling, let us help\nTap for support",
                'cta'   => 'Get Support', 'variables' => null, 'variation_group' => null, 'sort_order' => 2],

            ['key' => 'support_inactive_7_days', 'category' => 'support', 'sub_type' => 'inactive_7_days',
                'locale' => 'ar', 'priority' => 'medium',
                'title' => '🤲 نحن هنا لك',
                'body'  => "إذا كنت تواجه صعوبة، دعنا نساعدك\nاضغط للحصول على دعم",
                'cta'   => 'احصل على دعم', 'variables' => null, 'variation_group' => null, 'sort_order' => 2],

            ['key' => 'support_need_help', 'category' => 'support', 'sub_type' => 'need_help_reminder',
                'locale' => 'en', 'priority' => 'low',
                'title' => '💚 Do You Need Help?',
                'body'  => "If you're going through a difficult time\n\"If You Need Help Now\" section is here for you",
                'cta'   => 'Get Help', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

            ['key' => 'support_need_help', 'category' => 'support', 'sub_type' => 'need_help_reminder',
                'locale' => 'ar', 'priority' => 'low',
                'title' => '💚 هل تحتاج مساعدة؟',
                'body'  => "إذا كنت تمر بوقت صعب\nقسم \"إذا احتجت مساعدة الآن\" موجود لك",
                'cta'   => 'احصل على مساعدة', 'variables' => null, 'variation_group' => null, 'sort_order' => 3],

        ];
    }
}
