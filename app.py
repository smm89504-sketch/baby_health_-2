from flask import Flask, request, jsonify
from flask_cors import CORS
from datetime import datetime, timedelta
import numpy as np

app = Flask(__name__)
CORS(app)

# ==================== مودل توقع نمو الطفل ====================

class GrowthPredictionModel:

    GROWTH_STANDARDS = {
        'male': {
            0: {'weight': 3.5, 'height': 50},
            3: {'weight': 6.0, 'height': 61.6},
            6: {'weight': 8.2, 'height': 67.6},
            12: {'weight': 10.2, 'height': 75.1},
            24: {'weight': 13.5, 'height': 86.0},
        },
        'female': {
            0: {'weight': 3.3, 'height': 49.5},
            3: {'weight': 5.7, 'height': 61.0},
            6: {'weight': 7.8, 'height': 66.7},
            12: {'weight': 9.5, 'height': 73.4},
            24: {'weight': 12.5, 'height': 84.0},
        }
    }

    @staticmethod
    def predict_growth(age_months, current_weight, current_height, gender='male'):

        gender = gender.lower() if gender in ['male', 'female'] else 'male'
        standards = GrowthPredictionModel.GROWTH_STANDARDS[gender]
        closest_age = min(standards.keys(), key=lambda x: abs(x - age_months))
        standard = standards[closest_age]

        weight_percentile = GrowthPredictionModel._calculate_percentile(
            current_weight, standard['weight'], deviation=1.5
        )

        height_percentile = GrowthPredictionModel._calculate_percentile(
            current_height, standard['height'], deviation=3.0
        )

        avg_percentile = (weight_percentile + height_percentile) / 2

        if avg_percentile >= 95:
            status = 'مرتفع_جداً'
            message = 'النمو أعلى من المعدل الطبيعي بشكل ملحوظ.'
        elif avg_percentile >= 85:
            status = 'ممتاز'
            message = 'نمو الطفل ممتاز جداً.'
        elif avg_percentile >= 50:
            status = 'طبيعي'
            message = 'نمو الطفل ضمن المعدل الطبيعي.'
        elif avg_percentile >= 25:
            status = 'أقل_من_المعدل'
            message = 'النمو أقل من المتوسط.'
        elif avg_percentile >= 10:
            status = 'منخفض'
            message = 'النمو منخفض ويستحسن استشارة الطبيب.'
        else:
            status = 'خطير'
            message = '⚠️ النمو أقل من الطبيعي بشكل مقلق.'

        return {
            'age_months': age_months,
            'current_weight': current_weight,
            'current_height': current_height,
            'weight_percentile': round(weight_percentile, 2),
            'height_percentile': round(height_percentile, 2),
            'average_percentile': round(avg_percentile, 2),
            'status': status,
            'message': message,
            'confidence_score': 0.92,
            'recommendations': GrowthPredictionModel._get_recommendations(status),
            'prediction_date': datetime.now().isoformat()
        }

    @staticmethod
    def _calculate_percentile(current_value, standard_value, deviation=1.5):

        diff_from_standard = ((current_value - standard_value) / standard_value) * 100
        percentile = 50 + (diff_from_standard / (deviation * 2)) * 50
        return max(1, min(99, percentile))

    @staticmethod
    def _get_recommendations(status):

        recommendations = {
            'مرتفع_جداً': [
                'مراقبة النظام الغذائي',
                'تجنب الإفراط في التغذية',
                'مراجعة الطبيب لتقييم النمو'
            ],
            'ممتاز': [
                'الاستمرار بنفس نمط التغذية',
                'متابعة زيارات الطبيب الدورية',
                'تشجيع النشاط الحركي'
            ],
            'طبيعي': [
                'الحفاظ على جدول تغذية منتظم',
                'متابعة مراحل التطور',
                'تنظيم النوم'
            ],
            'أقل_من_المعدل': [
                'زيادة السعرات الحرارية بشكل صحي',
                'تقسيم الوجبات إلى كميات أصغر ومتكررة',
                'مراقبة الوزن أسبوعياً'
            ],
            'منخفض': [
                'استشارة طبيب الأطفال',
                'إجراء تحاليل عند الحاجة',
                'مراجعة نمط الرضاعة'
            ],
            'خطير': [
                '⚠️ مراجعة الطبيب فوراً',
                'فحص شامل للحالة الصحية',
                'متابعة طبية مستمرة'
            ]
        }

        return recommendations.get(status, [])


# ==================== تحليل بكاء الطفل ====================

class CrySentimentAnalysisModel:
    """ML-like Model for analyzing baby cry patterns"""
    
    CRY_TYPES = {
    'hunger': {
        'arabic_name': 'جوع',
        'description': 'بكاء منتظم ومتكرر',
        'intensity': 7,
        'solution': 'إرضاع الطفل فوراً'
    },
    'discomfort': {
        'arabic_name': 'انزعاج',
        'description': 'بكاء متقطع مع توقفات',
        'intensity': 6,
        'solution': 'تفقد الحفاض أو الملابس'
    },
    'pain': {
        'arabic_name': 'ألم',
        'description': 'بكاء حاد ومستمر',
        'intensity': 9,
        'solution': 'تفقد وجود إصابة أو حرارة'
    },
    'tired': {
        'arabic_name': 'نعاس',
        'description': 'بكاء خفيف ومتذمر',
        'intensity': 4,
        'solution': 'تهيئة بيئة نوم هادئة'
    },
    'overstimulated': {
        'arabic_name': 'تحفيز زائد',
        'description': 'بكاء متصاعد',
        'intensity': 8,
        'solution': 'إطفاء الأنوار وتقليل الضوضاء'
    },
    'colic': {
        'arabic_name': 'مغص',
        'description': 'بكاء طويل يستمر لساعات',
        'intensity': 8,
        'solution': 'تدليك البطن أو استشارة الطبيب'
    },
    'gas': {
        'arabic_name': 'غازات',
        'description': 'بكاء مع ثني الساقين',
        'intensity': 6,
        'solution': 'مساعدة الطفل على التجشؤ'
    },
    'teething': {
        'arabic_name': 'تسنين',
        'description': 'بكاء مع سيلان لعاب',
        'intensity': 5,
        'solution': 'استخدام عضاضة تبريد'
    }
}

    
from datetime import datetime


class CrySentimentAnalysisModel:

    CRY_TYPES = {
            "hunger": {
                "arabic_name": "جوع",
                "description": "الطفل يبكي لأنه جائع ويريد الرضاعة.",
                "solution": "إرضاع الطفل أو تقديم الحليب/الرضاعة حسب الجدول.",
                "intensity": "متوسط"
            },
            "discomfort": {
                "arabic_name": "انزعاج",
                "description": "الطفل قد يكون متسخ الحفاض أو يشعر بالحرارة/البرودة.",
                "solution": "تفقد الحفاض، ضبط درجة حرارة الغرفة، تهدئة الطفل.",
                "intensity": "خفيف"
            },
            "pain": {
                "arabic_name": "ألم",
                "description": "البكاء حاد ومصاحب بأعراض ألم.",
                "solution": "مراجعة الطبيب إذا استمر الألم.",
                "intensity": "عالي"
            },
            "tired": {
                "arabic_name": "تعب/نعاس",
                "description": "الطفل يشعر بالنعاس أو بحاجة للنوم.",
                "solution": "توفير بيئة هادئة ومظلمة للنوم.",
                "intensity": "خفيف"
            },
            "overstimulated": {
                "arabic_name": "فرط تحفيز",
                "description": "الطفل متحمس أو منبه بشكل زائد.",
                "solution": "تهدئة الغرفة وتقليل المحفزات.",
                "intensity": "متوسط"
            },
            "colic": {
                "arabic_name": "مغص",
                "description": "الطفل يبكي لفترات طويلة (>3 ساعات) غالباً بعد الظهر أو المساء.",
                "solution": "تدليك البطن، حمل الطفل، استشارة الطبيب إذا استمر.",
                "intensity": "عالي"
            }
        }
    @staticmethod
    def analyze_cry(duration_seconds, intensity_1_10, time_of_day,
                    last_fed_minutes_ago=None, age_months=None):

        """
        Advanced clinical-based cry analysis.
        Fully safe against bad frontend input.
        """

        # -------------------------------------------------
        # 🔒 1️⃣ Input Safety & Type Conversion
        # -------------------------------------------------

        try:
            duration_seconds = int(duration_seconds)
        except:
            duration_seconds = 0

        try:
            intensity_1_10 = int(intensity_1_10)
        except:
            intensity_1_10 = 0

        try:
            last_fed_minutes_ago = int(last_fed_minutes_ago) if last_fed_minutes_ago not in [None, ""] else None
        except:
            last_fed_minutes_ago = None

        try:
            age_months = int(age_months) if age_months not in [None, ""] else None
        except:
            age_months = None

        if time_of_day not in ['morning', 'afternoon', 'evening', 'night']:
            time_of_day = 'afternoon'

        # clamp values safely
        duration_seconds = max(0, duration_seconds)
        intensity_1_10 = max(0, min(10, intensity_1_10))

        analysis_results = []
        scores = {}

        # -------------------------------------------------
        # 2️⃣ Hunger Assessment
        # -------------------------------------------------

        base_hunger_score = 2

        if age_months is None:
            expected_interval = 180
        elif age_months <= 1:
            expected_interval = 120
        elif age_months <= 3:
            expected_interval = 150
        elif age_months <= 6:
            expected_interval = 180
        else:
            expected_interval = 240

        if last_fed_minutes_ago is not None:

            if last_fed_minutes_ago < 30:
                hunger_score = 1.5
            else:
                hunger_score = base_hunger_score
                time_ratio = last_fed_minutes_ago / expected_interval

                if time_ratio >= 1:
                    hunger_score += min(5, time_ratio * 3.5)

                if intensity_1_10 <= 6:
                    hunger_score += 1

                if duration_seconds > 180 and intensity_1_10 <= 6:
                    hunger_score += 1.2

                if intensity_1_10 >= 8:
                    hunger_score -= 1.5

            scores['جوع'] = max(1, min(9.5, hunger_score))
        else:
            scores['جوع'] = base_hunger_score

        # -------------------------------------------------
        # 3️⃣ Discomfort
        # -------------------------------------------------

        if 4 <= intensity_1_10 <= 7 and duration_seconds < 900:
            scores['عدم ارتياح'] = 6.5
        else:
            scores['عدم ارتياح'] = 3

        # -------------------------------------------------
        # 4️⃣ Pain
        # -------------------------------------------------

        pain_score = 2

        if intensity_1_10 >= 8:
            pain_score += 4

        if duration_seconds > 300:
            pain_score += 1.5

        if intensity_1_10 >= 9 and duration_seconds > 600:
            pain_score += 1

        scores['ألم'] = min(9.5, pain_score)

        # -------------------------------------------------
        # 5️⃣ Tiredness
        # -------------------------------------------------

        tired_score = 2

        if time_of_day in ['evening', 'night']:
            tired_score += 2

        if intensity_1_10 <= 5:
            tired_score += 3

        if duration_seconds < 900:
            tired_score += 1

        scores['تعب'] = min(8.5, tired_score)

        # -------------------------------------------------
        # 6️⃣ Overstimulation
        # -------------------------------------------------

        overstim_score = 2

        if time_of_day == 'evening' and intensity_1_10 >= 6:
            overstim_score += 3

        if duration_seconds < 600 and intensity_1_10 >= 6:
            overstim_score += 1

        scores['فرط تحفيز'] = min(8, overstim_score)

        # -------------------------------------------------
        # 7️⃣ Colic (Rule of 3)
        # -------------------------------------------------

        colic_score = 2

        if duration_seconds > 10800:
            colic_score += 4

            if time_of_day in ['afternoon', 'evening']:
                colic_score += 2

            if intensity_1_10 >= 7:
                colic_score += 1

        scores['مغص'] = min(9.5, colic_score)

        # -------------------------------------------------
        # 8️⃣ Sort Results
        # -------------------------------------------------

        sorted_scores = sorted(scores.items(), key=lambda x: x[1], reverse=True)

        primary_cause = sorted_scores[0][0]
        primary_score = sorted_scores[0][1]

        # -------------------------------------------------
        # 9️⃣ Build Analysis (Safe against missing CRY_TYPES)
        # -------------------------------------------------

        for cry_type, score in sorted_scores[:3]:

            cry_info = CrySentimentAnalysisModel.CRY_TYPES.get(cry_type, {
                "arabic_name": cry_type,
                "description": "غير محدد",
                "solution": "يرجى مراجعة المختص",
                "intensity": "متوسط"
            })

            analysis_results.append({
                'السبب': cry_info['arabic_name'],
                'نسبة_الثقة': round(min(0.95, score / 10), 2),
                'الوصف': cry_info['description'],
                'الحل_المقترح': cry_info['solution'],
                'مستوى_الشدة': cry_info['intensity']
            })

        return {
            'primary_cause': primary_cause,
            'confidence': round(min(0.95, primary_score / 10), 2),
            'detailed_analysis': analysis_results,
            'urgent_recommendation': CrySentimentAnalysisModel._get_urgency_note(intensity_1_10, duration_seconds),
            'analysis_timestamp': datetime.now().isoformat()
        }

    @staticmethod
    def _get_urgency_note(intensity, duration_seconds):

        if intensity >= 9 and duration_seconds > 600:
            return '⚠️ بكاء شديد جداً وممتد — يفضل مراجعة الطبيب فوراً.'

        elif intensity >= 8 and duration_seconds > 3600:
            return '⚠️ بكاء مرتفع لفترة طويلة — يحتاج تقييم طبي.'

        elif duration_seconds > 14400:
            return '⚠️ بكاء مستمر لفترة طويلة جداً — ينصح بفحص طبي.'

        else:
            return 'يمكن تجربة الحلول المقترحة مع مراقبة الحالة.'
SYMPTOMS_DATABASE = {
    "fever": {
        "arabic_name": "حمى",
        "normal_range": "36.5 - 37.5°م",
        "action": "استشر الطبيب إذا تجاوزت 38.5°م أو استمرت أكثر من 3 أيام",
        "home_care": "ملابس خفيفة، سوائل كافية، مراقبة الحرارة"
    },
    "cough": {
        "arabic_name": "سعال",
        "normal_range": "السعال الخفيف طبيعي",
        "action": "استشر الطبيب إذا كان شديداً أو مع صعوبة تنفس",
        "home_care": "جهاز ترطيب، سوائل دافئة"
    },
    "vomiting": {
        "arabic_name": "قيء",
        "normal_range": "مرات قليلة قد تكون طبيعية",
        "action": "استشر الطبيب إذا تكرر أكثر من 3 مرات",
        "home_care": "إعطاء سوائل بكميات صغيرة ومتكررة"
    },
    "diarrhea": {
        "arabic_name": "إسهال",
        "normal_range": "البراز الرخو شائع عند الرضع",
        "action": "راجع الطبيب عند ظهور جفاف",
        "home_care": "الاستمرار في الرضاعة"
    },
    "constipation": {
        "arabic_name": "إمساك",
        "normal_range": "يختلف حسب نوع التغذية",
        "action": "استشر الطبيب إذا لم يتبرز لأكثر من 3 أيام",
        "home_care": "تدليك البطن بلطف"
    },
}
# إضافة أكثر من 100 عرض إضافي
extra_symptoms = [
    "rash", "ear_pain", "runny_nose", "sore_throat", "fatigue",
    "loss_of_appetite", "bloating", "gas", "colic", "sneezing",
    "red_eyes", "eye_discharge", "teething", "chills", "sweating",
    "dry_skin", "itching", "bruising", "nosebleed", "difficulty_breathing",
    "wheezing", "bluish_lips", "seizure", "lethargy", "irritability",
    "swollen_gums", "mouth_ulcers", "white_tongue", "bad_breath",
    "swollen_lymph_nodes", "rapid_heartbeat", "slow_weight_gain",
    "poor_sleep", "night_crying", "frequent_urination",
    "strong_urine_smell", "blood_in_stool", "blood_in_urine",
    "persistent_hiccup", "skin_peeling", "sunburn",
    "allergic_reaction", "face_swelling", "hand_swelling",
    "foot_swelling", "vomit_with_blood", "green_stool",
    "black_stool", "yellow_skin", "pale_skin",
    "cold_hands", "cold_feet", "hot_skin",
    "dry_cough", "wet_cough", "chest_pain",
    "abdominal_pain", "back_pain", "neck_stiffness",
    "difficulty_swallowing", "loss_of_voice", "hoarseness",
    "ear_discharge", "eye_swelling", "blurred_vision",
    "excessive_sleep", "insomnia", "night_sweats",
    "hair_loss", "nail_changes", "joint_pain",
    "muscle_weakness", "delayed_walking", "delayed_speech",
    "delayed_teething", "head_tilt", "head_swelling",
    "soft_spot_bulging", "poor_balance", "frequent_falls",
    "excessive_thirst", "dry_mouth", "vomiting_after_feed",
    "spitting_up", "hiccups_after_feed", "arching_back",
    "refusing_feed", "crying_during_feed", "gasping",
    "snoring", "mouth_breathing", "bad_sleep",
    "skin_spots", "purple_rash", "yellow_eyes",
    "excessive_saliva", "drooling", "foul_stool_smell",
    "irregular_breathing", "weak_cry", "high_pitched_cry",
    "persistent_fever", "rapid_breathing", "slow_breathing"
]

for symptom in extra_symptoms:
    SYMPTOMS_DATABASE[symptom] = {
        "arabic_name": symptom.replace("_", " "),
        "normal_range": "يختلف حسب العمر والحالة",
        "action": "يفضل استشارة طبيب الأطفال لتقييم الحالة",
        "home_care": "مراقبة الطفل وتسجيل الأعراض"
    }

# ==================== FLASK ROUTES ====================

@app.route('/api/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'سليم',
        'service': 'محرك الذكاء الاصطناعي لرعاية الأطفال',
        'timestamp': datetime.now().isoformat()
    })


@app.route('/api/growth-prediction', methods=['POST'])
def growth_prediction():
    """
    Growth Prediction Endpoint
    
    Expected JSON:
    {
        "age_months": 6,
        "current_weight": 8.5,
        "current_height": 68,
        "gender": "male"
    }
    """
    try:
        data = request.get_json()
        
        # Validate input
        required_fields = ['age_months', 'current_weight', 'current_height']
        if not all(field in data for field in required_fields):
            return jsonify({'error': 'Missing required fields'}), 400
        
        age = float(data.get('age_months'))
        weight = float(data.get('current_weight'))
        height = float(data.get('current_height'))
        gender = data.get('gender', 'male')
        
        # Validate ranges
        if not (0 <= age <= 24):
            return jsonify({'error': 'Age must be between 0-24 months'}), 400
        if not (1 <= weight <= 20):
            return jsonify({'error': 'Weight must be between 1-20 kg'}), 400
        if not (40 <= height <= 100):
            return jsonify({'error': 'Height must be between 40-100 cm'}), 400
        
        # Get prediction
        prediction = GrowthPredictionModel.predict_growth(age, weight, height, gender)
        
        return jsonify({
            'success': True,
            'data': prediction
        })
    
    except ValueError as e:
        return jsonify({'error': str(e)}), 400
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/cry-analysis', methods=['POST'])
def cry_analysis():

    data = request.get_json(silent=True)

    if not data:
        return jsonify({"error": "Invalid JSON"}), 400

    try:
        result = CrySentimentAnalysisModel.analyze_cry(
            duration_seconds=data.get("duration_seconds"),
            intensity_1_10=data.get("intensity_1_10"),
            time_of_day=data.get("time_of_day"),
            last_fed_minutes_ago=data.get("last_fed_minutes_ago"),
            age_months=data.get("age_months")
        )

        return jsonify({'success': True, 'data': result}), 200

    except Exception as e:
        print("🔥 ERROR:", str(e))
        return jsonify({"error": str(e)}), 500
@app.route('/api/symptom-guidance', methods=['POST'])
def symptom_guidance():
    try:
        data = request.get_json()

        symptom = data.get('symptom', '').lower()
        temperature = float(data.get('temperature', 36.5))
        duration = int(data.get('duration_hours', 1))
        age = int(data.get('age_months', 6))

        if symptom not in SYMPTOMS_DATABASE:
            return jsonify({
                "success": False,
                "message": "العرض غير موجود في قاعدة البيانات",
                "available_symptoms_count": len(SYMPTOMS_DATABASE)
            }), 400

        response = SYMPTOMS_DATABASE[symptom]

        # ==== تحديد شدة الحالة ====
        if age < 3:  # أقل من 3 أشهر
            if temperature >= 39:
                urgency = "HIGH"
                severity = "حمى شديدة"
            elif temperature >= 38:
                urgency = "MEDIUM"
                severity = "حمى متوسطة"
            else:
                urgency = "LOW"
                severity = "حمى خفيفة"
        else:  # 3 أشهر فأكثر
            if temperature >= 40:
                urgency = "HIGH"
                severity = "حمى شديدة"
            elif temperature >= 38.5:
                urgency = "MEDIUM"
                severity = "حمى متوسطة"
            else:
                urgency = "LOW"
                severity = "حمى خفيفة"

        return jsonify({
            "success": True,
            "symptom_key": symptom,
            "symptom_name": response["arabic_name"],
            "data": response,
            "urgency_level": urgency,
            "severity": severity,
            "database_size": len(SYMPTOMS_DATABASE),
            "timestamp": datetime.now().isoformat()
        })

    except Exception as e:
        return jsonify({
            "success": False,
            "error": "حدث خطأ في معالجة الطلب"
        }), 500

    except ValueError as ve:
        return jsonify({
            "success": False,
            "error": f"خطأ في نوع البيانات: {str(ve)}"
        }), 400
    except Exception as e:
        return jsonify({
            "success": False,
            "error": f"حدث خطأ في معالجة الطلب: {str(e)}"
        }), 500
@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'الرابط غير موجود'}), 404


@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'خطأ داخلي في الخادم'}), 500


if __name__ == '__main__':
    app.run(
        host='127.0.0.1',
        port=5000,
        debug=True,
        threaded=True
    )
