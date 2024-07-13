<?php
namespace  HarmonisedProgression\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Response;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Text;

/**
 * HarmonisedProgressionsController Controller
 * @dev :: All 'Texts' In English as they will be translated from UI
 *
 * @property \HarmonisedProgressionsController\Model\Table\HarmonisedProgressionsTable  $HarmonisedProgressions
 */
class HarmonisedProgressionsController extends AppController
{

    /** ---------------- INITIALIZATION  ---------------- */

    public function initialize() {
        parent::initialize();
        // $this->loadComponent('OetExtras.OetScholarship');  // Scholarship :: @Miché
        $this->attachAngularModules();                     // Scholarship :: @Miché
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
    }
    
    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {

    }


    /** ---------------- DEFINING ACTIONS   ---------------- */
   
    private function  attachAngularModules()
    {
        $action = $this->request->action;
        switch ($action) { 
            case 'dashboard':
                    $this->Angular->addModules([
                        'alert.svc',
                        'harmonisedprogressions.dashboard.ctrl',
                        'harmonisedprogressions.dashboard.svc'
                    ]);
                    // Log::write('debug', ' harmonisedprogressions Over dashboard  5... ');
                break;
        }
    }

    public function dashboard(){
        // Log::write('debug', 'Running Progressions Harmonisées ');

        $this->render('harmonised_dashboard');
        $this->set('ngController', 'HarmonisedProgressionsDashboardCtrl as HarmonisedProgressionsDashboardCtrl');
    }
    /**
     * Get Grade Data 
    */
    public function getGradeData($params=null)
    {
        // Get Params 
        $this->autoRender = false;
        [$academicPeriodId, $educationGradeId] = explode('_',$params); 
        // Log::write('debug', 'getGradeData $requestData ::'. (json_encode($params)). ' P::'. $academicPeriodId.' G::'.$educationGradeId );

        // Var Setting
        $EducationContentDefinitionsTable = TableRegistry::get('education_content_definitions');
        // $HarmonisedProgressionsTable = TableRegistry::get('harmonised_progressions');


        // Retrieve Data from EducationsContent && Grade
        $gradesData = $EducationContentDefinitionsTable
            ->find()
            ->select([
                'id' => 'G.id',
                'name' => 'G.name'
            ])
            ->join([
                'table'=>'education_grades',
                'alias'=>'G',
                'type'=>'LEFT',
                'conditions' => ['G.id = '. $EducationContentDefinitionsTable->aliasField('education_grade_id')]
            ])
            ->where([
                $EducationContentDefinitionsTable->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->toArray();
        // Log::write('debug', 'result getGradeData  ::'. (json_encode($gradesData)) );
        echo json_encode($gradesData);
        die();
    }
    /*
    * Get Subject Data 
    */
    public function getSubjectData($params=null)
    {
        // Get Params 
        $this->autoRender = false;
        [$academicPeriodId, $educationGradeId, $educationSubjectId] = explode('_',$params); 
        // Log::write('debug', 'getGradeData $requestData ::'. (json_encode($params)). ' P::'. $academicPeriodId.' G::'.$educationGradeId.' S::'.$educationSubjectId );

        // Var Setting
        $EducationContentDefinitionsTable = TableRegistry::get('education_content_definitions');
        $HarmonisedProgressionsTable = TableRegistry::get('harmonised_progressions');

        // Retrieve Data from EducationsContent && Grade
        $subjecsData = $EducationContentDefinitionsTable
            ->find()
            ->select([
                'id' => 'S.id',
                'name' => 'S.name'
            ])
            ->join([
                'table'=>'education_subjects',
                'alias'=>'S',
                'type'=>'LEFT',
                'conditions' => ['S.id = '. $EducationContentDefinitionsTable->aliasField('education_subject_id')]
            ])
            ->where([
                $EducationContentDefinitionsTable->aliasField('academic_period_id') => $academicPeriodId,
                $EducationContentDefinitionsTable->aliasField('education_grade_id') => $educationGradeId 
            ])
            ->toArray();
        // Log::write('debug', 'result getSubjectData  ::'. (json_encode($subjecsData)) );
        echo json_encode($subjecsData);
        die();
    }
    /**
     * Get Dashboard Data 
    */
    public function getDashboardData($params=null)
    {
        // Get Params 
        $this->autoRender = false;
        [$academicPeriodId, $educationGradeId, $educationSubjectId] = explode('_',$params); 
        // Log::write('debug', 'getDashboardData $requestData ::'. (json_encode($params)). ' P::'. $academicPeriodId.' G::'.$educationGradeId.' S::'.$educationSubjectId );

        // Var Setting
        $HarmonisedProgressionsTable = TableRegistry::get('harmonised_progressions');
        $InstitutionsTable = TableRegistry::get('institutions');
        $DrenaTable = TableRegistry::get('areas');
        $progression_etab_names = $progression_drena_names = [];
        $progression_etab_names_transformed = $progression_drena_names_transformed = [];

        // Retrieve Data from Table ... 
        $hc = $HarmonisedProgressionsTable
            ->find('all')
            ->where([
                $HarmonisedProgressionsTable->aliasField('academic_period_id') => $academicPeriodId, 
                $HarmonisedProgressionsTable->aliasField('education_subject_id') => $educationSubjectId, 
                $HarmonisedProgressionsTable->aliasField('education_grade_id') => $educationGradeId
            ])
            ->first();
         
        if($hc) // Getting School + DRENA Names 
        {
            // Getting School Names 
            $progression_etab_obj = json_decode($hc['progression_etab'], true);
            $progression_etab_ids =  array_keys($progression_etab_obj);
            $progression_etab_names = $InstitutionsTable
                ->find()
                ->select(['id', 'name'])
                ->where([
                    $InstitutionsTable->aliasField('id')." IN" => $progression_etab_ids
                ])
                ->toArray();
            foreach ($progression_etab_names as $k => $v) {
                $progression_etab_names_transformed[ $v['id']  ] = $v['name'];
            }

            // Getting DRENA Names 
            $progression_drena_obj = json_decode($hc['progression_drena'], true);
            $progression_drena_ids =  array_keys($progression_drena_obj);
            $progression_drena_names = $DrenaTable
                ->find()
                ->select(['id', 'name'])
                ->where([
                    $DrenaTable->aliasField('id')." IN" => $progression_drena_ids
                ])
                ->toArray();
            foreach ($progression_drena_names as $k => $v) {
                $progression_drena_names_transformed[ $v['id']  ] = $v['name'];
            }
        }

        $r = array(
            'progression_class' => json_decode($hc['progression_class'], true),
            'progression_class_summary' =>  json_decode($hc['progression_class_summary'], true),
            'progression_etab' =>  json_decode($hc['progression_etab'], true),
            'progression_drena' =>  json_decode($hc['progression_drena'], true),
            'progression_national_summary' =>  json_decode($hc['progression_national_summary'], true),
            'course_titles_subtitles' =>  json_decode($hc['course_titles_subtitles'], true),
            'progression_etab_names' => $progression_etab_names,
            'progression_drena_names' => $progression_drena_names,
            'progression_drena_names_transformed' => $progression_drena_names_transformed,
            'progression_etab_names_transformed' => $progression_etab_names_transformed
        );
        echo json_encode($r);
        die();
    }
}
