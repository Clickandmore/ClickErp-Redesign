<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\AboutUsAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\AboutUs as objModel;
use App\Http\Requests\ingaz\AboutUsRequest as objRequest;
use App\Models\ingaz\AboutUs;
use App\Models\Feature;
use Illuminate\Http\Request;


class AboutUsController extends Controller
{

    public $folderPath = "admin.ingaz.about_us";
    public $postData = ['image', 'image_vision', 'image_message', 'image_value', "title:ar", "title:en", "description:ar", "description:en", "mission:ar", "mission:en", "vision:ar", "vision:en", "value:ar", "value:en"];
    public $mainRoute = "about-us";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action)
    {
        $about = AboutUs::first();

        if (!$about) {
            $defaultContent = [
                'ar' => [
                    'title' => 'من نحن',
                    'description' => 'بلاتينيوم الشرقية للاستقدام مرخصة من وزارة العمل والموارد البشرية لخدمات التوسط في استقدام العمالة الوافدة ومقرها المنطقة الشرقية في المملكة العربية السعودية، وحرصاً منا على تقديم خدمات متميزة تم إطلاق منصة رقمية متكاملة لتبسيط عملية الاستقدام وجعلها أكثر كفاءة وسهولة لعملائنا بما يتوافق مع رؤية المملكة.',
                    'mission' => '<p><strong>((الإنسانية))</strong> نفتخر بعملنا كونه يسهم بتوفير موارد ودخل جيّد لأسرة وفرد بحاجة لهذه الموارد لدعم أسرته وبناء مستقبله، وبالمقابل توفير سبل الراحة للأسرة السعودية والمقيمة في وطننا المعطاء.</p><p>((( احتياجاتكم أولوياتنا ))).</p>',
                    'vision' => '<p>أن نكون الخيار الأول للاستقدام في المملكة العربية السعودية، وذلك بالتفرد بتقديم أجود الخدمات وانتقاء أجود الكفاءات، والحرص على راحة العملاء وحفظ حقوقهم، ونحقق ذلك بعد اكتساب ثقة العملاء وتوصيتهم بنا لمن حولهم.</p>',
                    'value' => '<p>قيمنا تركز على رضى العميل، حيث نولي الأولوية للجودة والكمال في كل خدماتنا. نحن روح الفريق لتحقيق النجاح، نحرص على السرعة والإنجاز لتلبية احتياجات عملائنا، ونعتبر النزاهة والشفافية أساساً في كل تعاملاتنا.</p>',
                ],
                'en' => [
                    'title' => 'About Us',
                    'description' => 'Platinum Eastern Recruitment is licensed by the Ministry of Human Resources and Social Development to mediate the recruitment of domestic workers. We are headquartered in the Eastern Province of the Kingdom of Saudi Arabia, and to deliver exceptional services we launched an integrated digital platform that simplifies recruitment and makes it more efficient and convenient for our clients in line with the Kingdom’s vision.',
                    'mission' => '<p><strong>((Humanity))</strong> We are proud that our work helps provide sustainable income for individuals and families who depend on it to build their future, while ensuring comfort for Saudi and resident families in our generous nation.</p><p>((( Your needs are our priority ))).</p>',
                    'vision' => '<p>To be the first choice for recruitment in the Kingdom of Saudi Arabia by delivering outstanding services, selecting the best talents, safeguarding customers’ comfort and rights, and continuously earning their trust and referrals.</p>',
                    'value' => '<p>Our values focus on customer satisfaction. We prioritise quality and excellence in every service, embrace teamwork to achieve success, act with speed and efficiency, and consider integrity and transparency the foundation of all our dealings.</p>',
                ],
            ];

            $about = AboutUs::create([
                'ar' => $defaultContent['ar'],
                'en' => $defaultContent['en'],
            ]);
        }

        $featuresList = Feature::all();

        return view('admin.ingaz.about_us.index', compact('about', 'featuresList'));
    }


    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, objAction $action)
    {
        $data = $request->only($this->postData);
        if (isset($data['image'])) {
            $data['image'] = uploadFile($data['image'], 'images/abouts/' , 1);
        }
        if (isset($data['image_vision'])) {
            $data['image_vision'] = uploadFile($data['image_vision'], 'images/abouts/' , 1);
        }
        if (isset($data['image_message'])) {
            $data['image_message'] = uploadFile($data['image_message'], 'images/abouts/' , 1);
        }
        if (isset($data['image_value'])) {
            $data['image_value'] = uploadFile($data['image_value'], 'images/abouts/' , 1);
        }
        $data = $action->store($data);
        return jsonSuccess($data, null, 201);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $dataInsert = $request->only($this->postData);

        // Get the first setting record or create one if it doesn't exist
        $setting = AboutUs::findOrFail($id);


        $action->updateSetting($setting->id, $dataInsert);
        return jsonSuccess(null, null, 201);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
