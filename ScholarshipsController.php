<?php
namespace Scholarship\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Network\Response;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;
use App\Controller\AppController;
Use Cake\Log\Log;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Text;

class ScholarshipsController extends AppController
{
    /**
     * List Of Scholarship Params, Const 
     * @Miché
    */
    const SCHOLARSHIP_PARAM__BOURSE_TGP = "BOURSE_TGP";
    const SCHOLARSHIP_PARAM__BOURSE_MO = "BOURSE_MO";
    const SCHOLARSHIP_PARAM__BOURSE_B_1_1C = "BOURSE_B_1_1C";
    const SCHOLARSHIP_PARAM__BOURSE_B_2_2C = "BOURSE_B_2_2C";
    const SCHOLARSHIP_PARAM__BOURSE_B_05_1C = "BOURSE_B_05_1C";
    const SCHOLARSHIP_PARAM__BOURSE_B_05_2C = "BOURSE_B_05_2C";
    const SCHOLARSHIP_PARAM__BOURSE_MONTANT_ALLOUE = "BOURSE_MONTANT_ALLOUE";
    const SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 = "BOURSE_MONTANT_ELEVE_1";
    const SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 = "BOURSE_MONTANT_ELEVE_05";
    const SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_1C = "BOURSE_LOT_ATTRIBUTION_1C";
    const SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_2C = "BOURSE_LOT_ATTRIBUTION_2C";

    const SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MIN = "BOURSE_B_05_1C_MIN";
    const SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MAX = "BOURSE_B_05_1C_MAX";
    const SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MIN = "BOURSE_B_05_2C_MIN";
    const SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MAX = "BOURSE_B_05_2C_MAX";

    const SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E = "AGE_MAXI_ATTRIBUTION_BOURSE_6E";
    const SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE = "AGE_MAXI_ATTRIBUTION_BOURSE_2NDE";
        
    const SCHOLARSHIP_PARAM__ELIGIBLE_STEP_GLOBAL_ASSIGNEE = "WORKLOW_INE_VALIDATEUR_GLOBAL_ETAPE_ELIGIBLE";

    const SCHOLARSHIP_REGEX_LIST = [
        'MARK' => '/^(\d+\.?\d+)$|^(\d)$/',  // Can be 0
        'MARK_INTERVAL' => '/^(\d+\.?\d+)\-(\d+\.?\d+)$/',
        'AMOUNT' => '/^(\d+_)+(\d+)$|^(\d+\.?\d+)$/',
        'PROPORTION' => '/^(\d+\.?\d+)$/',

        'AMOUNT_NORMALIZE' => '/_/',

        'INT' => '/^(\d+)$/',
        'FLOAT' => '/^(\d+\.\d+)$/',
        'INE' => '/^(\w+)$/',
    ];
    const SCHOLARSHIP_WORKFLOW_ID = 20;
    const SCHOLARSHIP_WORKFLOW_STEP_CODE = [
        "ELIGIBLE" => "DOB_ELIGIBLE",
    ];
    
    const SCHOLARSHIP_BOURSIER_LOT = [
        '1BOURSIER' => 'BOURSIER',
        '05_BOURSIER' => '1/2 BOURSIER',
    ];
    const SCHOLARSHIP_BOURSIER_CATEGORY = [
        'RENOUVELLEMENT' => 'RENOUVELLEMENT',
        'ATTRIBUTION' => 'ATTRIBUTION',
    ];
    const SCHOLARSHIP_DECO_EXAM_TYPE_ID = [
        'CEPE' => 1,
        'BEPC' => 2,
        'BAC' => 3
    ];

    const SCHOLARSHIP_PARAMS = [
        self::SCHOLARSHIP_PARAM__BOURSE_TGP => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_TGP, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['MARK'], 'DESC'=> "ATTRIBUTION -- TGP CM2 -> 6è -- 160"],  
        self::SCHOLARSHIP_PARAM__BOURSE_MO  => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_MO, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['MARK'], 'DESC'=> "ATTRIBUTION -- MO 3e->2nde -- 14"],  

        self::SCHOLARSHIP_PARAM__BOURSE_B_1_1C => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_B_1_1C, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['MARK'], 'DESC'=> "RENOUVELLEMENT -- Boursier 1er Cycle -- 12"],
        self::SCHOLARSHIP_PARAM__BOURSE_B_2_2C => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_B_2_2C, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['MARK'], 'DESC'=> "RENOUVELLEMENT -- Boursier 2è Cycle -- 11"],

        self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['MARK_INTERVAL'], 'DESC'=> "RENOUVELLEMENT -- 1/2 Boursier 1er Cycle -- 11-11.99"],      // 'BOURSE_B_05_1C_MIN' => 0,  'BOURSE_B_05_1C_MAX' => 0, 
        self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['MARK_INTERVAL'], 'DESC'=> "RENOUVELLEMENT -- 1/2 Boursier 2è Cycle -- 10-10.99"],       // 'BOURSE_B_05_2C_MIN' => 0, 'BOURSE_B_05_2C_MAX' => 0, 
        
        self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ALLOUE  => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ALLOUE, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['AMOUNT'], 'DESC'=> "BUDGET -- Montant Budget National Bourse -- 1_100_000_000 | 1100000000"],
        self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['AMOUNT'], 'DESC'=> "BUDGET -- Montant par Elève Boursier -- 36_000 | 36000 "],
        self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['AMOUNT'], 'DESC'=> "BUDGET -- Montant par Elève 1/2 Boursier -- 18_000 | 18000"],
        
        self::SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_1C => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_1C, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['PROPORTION'], 'DESC'=> "PROPORTION -- Lot Boursier 1er Cycle (/3) -- 0.66"],
        self::SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_2C => ['CODE' => self::SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_2C, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['PROPORTION'], 'DESC'=> "PROPORTION -- Lot Boursier 2e Cycle (/3) -- 0.33"],

        self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E => ['CODE' => self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['INT'], 'DESC'=> "Age Maxi Attribution Bourse 6e  -- 15"],  
        self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE => ['CODE' => self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['INT'], 'DESC'=> "Age Maxi Attribution Bourse 2nde  -- 18"],  
        self::SCHOLARSHIP_PARAM__ELIGIBLE_STEP_GLOBAL_ASSIGNEE => ['CODE' => self::SCHOLARSHIP_PARAM__ELIGIBLE_STEP_GLOBAL_ASSIGNEE, 'FORMAT'=> self::SCHOLARSHIP_REGEX_LIST['INE'], 'DESC'=> "Validateur INE All  -- 12748626727"],  
    
    ];
    const SCHOLARSHIP_CMU_IDENTITY_TYPE_ID = 164; 
    const SCHOLARSHIP_CIV_NATIONALITY_ID = 1; 
    const SCHOLARSHIP_STUDENT_AFFECTE_ID = 1; 
    const SCHOLARSHIP_EDUCATION_GRADES_CODE_RENEWALS = [ // ID Of Grades will change each Academic Period but Code doesn't Change   |  (From DB / OpenEMIS/Adminsitration/StructureEducation)
                '5e' => '09', 
                '4e' => '10',
                '3e ' => '11',
                '2ndeA' => '12',
                '2ndeC' => '13',
                '1ereA1' => '14',
                '1ereA2' => '15',
                '1ereC' => '16',
                '1ereD' => '17',
                'TleA1' => '18',
                'TleA2' => '19',
                'TleC' => '20',
                'TleD' => '21'
    ];
    const SCHOLARSHIP_EDUCATION_GRADES_CODE_ATTRIBUTIONS = [ // ID Of Grades will change each Academic Period but Code doesn't Change   |  (From DB / OpenEMIS/Adminsitration/StructureEducation)
                '6e' => '08', 
                '2nde' => '12_13',
    ];
    const SCHOLARSHIP_EDUCATION_GRADES_CODE_1CYCLE = [ '09', '10', '11'];
    const SCHOLARSHIP_EDUCATION_GRADES_CODE_2CYCLE = [ '12','13', '14','15', '16','17', '18','19', '20','21'];
    const SCHOLARSHIP_CHUNCK_LIMIT = 1000; 
    const SCHOLARSHIP_CI_DOB_TYPE_ID = 5; 
    
    const SCHOLARSHIP_CONFIGITEM_AVAILABLE_TO_SCHOOLS_CODE  = 'AVAILABLE_TO_SCHOOLS';
    const SCHOLARSHIP_CONFIGITEM_AVAILABLE_TO_SCHOOLS_ID  = 1;

    /** < ------------------ #OET:ScholarShip Params -  Miché ------------------ */

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('User.Users');
        $this->loadComponent('Scholarship.ScholarshipTabs');
        $this->loadComponent('OetExtras.OetScholarship');  // Scholarship :: @Miché
        $this->attachAngularModules();                     // Scholarship :: @Miché
    }

    
    // ------------------- #OET : Load Modules : @Miché -----------------------
    private function  attachAngularModules()
    {
        $action = $this->request->action;
        switch ($action) { 
            case 'Applications':
                break;
             case 'SummaryScholarshipApplications':
                    $this->Angular->addModules([
                        'alert.svc',
                        'scholarships.applications.dashboard.ctrl',  
                        'scholarships.applications.dashboard.svc'
                    ]);
                    Log::write('debug', 'Over AttachAngularModules  4... ');
                break;
        }
    }
    // < ------------------- #OET : Load Modules : @Miché -----------------------
    // CAv4
    public function Scholarships()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Scholarships']);
    }
    
    /** >  ------------ #OET  Scholarship Commitment Form  && API  @Miché ----------------  */
       /** >  ------------ #OET  Scholarship CommitmentForm && API && Btn Cancel/Send ::   @Miché ----------------  */
       public function getScholarshipStudentData(){
        $this->autoRender = false;
        if( !($this->request->is(['get']))) return false;
     
        // -------------- Getting Query Parameter -------------------------------------------
        $p = array(
            "applicant_id" =>  $this->request->query("a_id"),
            "scholarship_id" =>  $this->request->query("s_id"),
            "current_institution_id" =>  $this->request->query("c_id"),
			"option" =>  $this->request->query("o"),
        );

        // -------------- Fetch Data From DB ::   --------------------
        $resp = array();

        $ScholarshipApplicationsTable = TableRegistry::get('scholarship_applications'); 
        $InstitutionStudentsTable = TableRegistry::get('institution_students'); 
        $StudentGuardiansTable = TableRegistry::get('student_guardians'); 
        $UsersContactTable = TableRegistry::get('user_contacts'); 
        
        $d1 = $ScholarshipApplicationsTable   // Get Student Data : Name, Scholarship, Genders...  
            ->find()
            ->select([
                $ScholarshipApplicationsTable->aliasField('id'),
                $ScholarshipApplicationsTable->aliasField('applicant_id'),
                $ScholarshipApplicationsTable->aliasField('scholarship_id'),
                $ScholarshipApplicationsTable->aliasField('requested_amount'),
                $ScholarshipApplicationsTable->aliasField('status_id'),
                $ScholarshipApplicationsTable->aliasField('assignee_id'),

                $ScholarshipApplicationsTable->aliasField('category'),
                $ScholarshipApplicationsTable->aliasField('lot'),

                // 'applicant_id' => 'U.id',
                'applicant_openemis_no' => 'U.openemis_no',
                'applicant_first_name' => 'U.first_name',
                'applicant_middle_name' => 'U.middle_name',
                'applicant_third_name' => 'U.third_name',
                'applicant_last_name' => 'U.last_name',
                'applicant_preferred_name' => 'U.preferred_name',
                'applicant_gender_id' => 'U.gender_id',
                'applicant_date_of_birth' => 'U.date_of_birth',
                'applicant_identity_type_id' => 'U.identity_type_id',
                'applicant_identity_number' => 'U.identity_number',
                'applicant_photo_content' => 'U.photo_content', // 

                'area_administrative_code' => 'A.code',
                'area_administrative_name' => 'A.name',

                'nationality_id' => 'N.id',
                'nationality_name' => 'N.name',
                'nationality_identity_type_id' => 'N.identity_type_id',

                'gender_code' => 'G.code',
                'gender_name' => 'G.name',

                'identity_type_id' => 'T.id',
                'identity_type_name' => 'T.name',
                'identity_type_national_code' => 'T.national_code',

                'scholarship_code' => 'S.code',
                'scholarship_name' => 'S.name',
                'scholarship_academic_period_id' => 'S.academic_period_id'
            ])
            ->join([
                'table'=>'security_users',
                'alias'=>'U',
                'type'=>'LEFT',
                'conditions' => ['U.id = '. $ScholarshipApplicationsTable->aliasField('applicant_id')]
            ])
            ->join([
                'table'=>'area_administratives',
                'alias'=>'A',
                'type'=>'LEFT',
                'conditions' => ['A.id = U.birthplace_area_id'] 
            ])
            ->join([
                'table'=>'nationalities',
                'alias'=>'N',
                'type'=>'LEFT',
                'conditions' => ['N.id = U.nationality_id']
            ])
            ->join([
                'table'=>'genders',
                'alias'=>'G',
                'type'=>'LEFT',
                'conditions' => ['G.id = U.gender_id']
            ])
            ->join([
                'table'=>'identity_types',
                'alias'=>'T',
                'type'=>'LEFT',
                'conditions' => ['T.id = U.identity_type_id']
            ])
            ->join([
                'table'=>'scholarships',
                'alias'=>'S',
                'type'=>'LEFT',
                'conditions' => ['S.id = '. $ScholarshipApplicationsTable->aliasField('scholarship_id')]
            ])
            ->where([
                $ScholarshipApplicationsTable->aliasField('applicant_id') => $p['applicant_id'],        // Ex :: 23 
                $ScholarshipApplicationsTable->aliasField('scholarship_id') => $p['scholarship_id']     // Ex 3   
            ])  
            ->first() 
            ;

        $d2 = $InstitutionStudentsTable  // Get Student Level, InstitutionName, StudentTresorPay 
            ->find()
            ->select([
                $InstitutionStudentsTable->aliasField('id'),
                $InstitutionStudentsTable->aliasField('tresor_pay_number'),

                'institution_id' => 'I.id',
                'institution_code' => 'I.code',
                'institution_name' => 'I.name',
                'institution_telephone' => 'I.telephone',

                'grade_id' => 'G.id',
                'grade_code' => 'G.code',
                'grade_name' => 'G.name'
            ])
            ->join([
                'table'=>'institutions',
                'alias'=>'I',
                'type'=>'LEFT',
                'conditions' => ['I.id = '. $InstitutionStudentsTable->aliasField('institution_id')]
            ])
            ->join([
                'table'=>'education_grades',
                'alias'=>'G',
                'type'=>'LEFT',
                'conditions' => ['G.id = '. $InstitutionStudentsTable->aliasField('education_grade_id')]
            ])
            ->where([
                $InstitutionStudentsTable->aliasField('student_id') => $p['applicant_id'],
                $InstitutionStudentsTable->aliasField('institution_id')  => $p['current_institution_id']
            ])
            ->first()
            ;

        $d3 = $StudentGuardiansTable   // Get Student Parent (Signataire)  Name ...
            ->find()
            ->select([
                $StudentGuardiansTable->aliasField('id'),
                $StudentGuardiansTable->aliasField('profession'), // Signataire

                'guardian_id' => 'U.id',
                'guardian_openemis_no' => 'U.openemis_no',
                'guardian_first_name' => 'U.first_name',
                'guardian_middle_name' => 'U.middle_name',
                'guardian_third_name' => 'U.third_name',
                'guardian_last_name' => 'U.last_name',
                'guardian_preferred_name' => 'U.preferred_name',
                'guardian_gender_id' => 'U.gender_id',
                'guardian_date_of_birth' => 'U.date_of_birth',
                'guardian_identity_type_id' => 'U.identity_type_id',
                'guardian_identity_number' => 'U.identity_number',
                'guardian_email' => 'U.email',
                'guardian_address' => 'U.address',

                'identity_id' => 'T.id',
                'identity_name' => 'T.name',
                'identity_national_code' => 'T.national_code'
            ])
            ->join([
                'table'=>'security_users',
                'alias'=>'U',
                'type'=>'LEFT',
                'conditions' => ['U.id = '. $StudentGuardiansTable->aliasField('guardian_id')]
            ])
            ->join([
                'table'=>'identity_types',
                'alias'=>'T',
                'type'=>'LEFT',
                'conditions' => ['T.id = U.identity_type_id']
            ])
            ->where([
                $StudentGuardiansTable->aliasField('parent_signataire_boursier') => 1, 
                $StudentGuardiansTable->aliasField('student_id') => $p['applicant_id']
            ])
            ->first();
        $student_parent_id = $d3 ['guardian_id'] ?? -1;
        
        $d4 = $UsersContactTable   // Get Student Parent (Signataire) Contact  ...
            ->find()
            ->select([
                $UsersContactTable->aliasField('id'),
                $UsersContactTable->aliasField('value'),
              
                'contact_type_id' => 'C.id',
                'contact_type_name' => 'C.name',
                'contact_type_national_code' => 'C.national_code'
            ])
            ->join([
                'table'=>'security_users',
                'alias'=>'U',
                'type'=>'LEFT',
                'conditions' => ['U.id = '. $UsersContactTable->aliasField('security_user_id')]
            ])
            ->join([
                'table'=>'contact_types',
                'alias'=>'C',
                'type'=>'LEFT',
                'conditions' => ['C.id = '.$UsersContactTable->aliasField('contact_type_id')]
            ])
            ->where([
                $UsersContactTable->aliasField('security_user_id') =>  $student_parent_id
            ])
            ->toArray();
        $parent_contact_list  = [];
        foreach ($d4 as $k => $v) {
            $parent_contact_list[] =  $v['value'];
        }
        
        // -------------- ::/ Response   ----------------------------------------------------
        $resp [ 'STUDENT' ] = array(
            'last_name' =>  $d1['applicant_last_name'],                     // name
            'others_name' =>  ($d1['applicant_first_name'].' '. $d1['applicant_middle_name']. ' '. $d1['applicant_third_name']),     // Prenoms
            'date_of_birth' =>  $d1['applicant_date_of_birth'] ?? "-",      // DateNaissance
            'place_of_birth' =>  $d1['area_administrative_name'] ?? "-",    // LieuNaissance
            'nationality' =>  $d1['nationality_name'] ?? "-",               // Nationalité
            'sex' => $d1['gender_name'] ?? "-",                             // Sexe
            'matricule' =>  $d1['applicant_openemis_no'] ,                  // OpenEMIS No / Matricule 
            'level_name' =>  $d2['grade_name'] ?? "-",                      // Niveau
            'statut' =>  $d1['lot'],                                        // Statut
            'cmu_number' =>  "-",                                                  // CMUNumber SET_BELOW
            'institution_name' =>  $d2['institution_name'] ?? "-",                // Etablissement
            'tresor_pay_number' =>  $d2['tresor_pay_number'] ?? '0000000000',     // NumeroTresorPay
            'photo_content' =>  $d1['applicant_photo_content'] ?  base64_encode($d1['applicant_photo_content']): "" // /oet_extras/img/student_default_image_dob.png",     // NumeroTresorPay
        ); 
        if($d1['applicant_identity_type_id'] ==  self::SCHOLARSHIP_CMU_IDENTITY_TYPE_ID)  //  Set CMUNumber
        {
            $resp [ 'STUDENT' ] ['cmu_number'] = $d1['applicant_identity_number']; 
        }

        $resp [ 'PARENT' ] = array(
            'last_name' =>  $d3['guardian_last_name'],                              // name
            'others_name' =>  ($d3['guardian_first_name'].' '. $d3['guardian_middle_name']. ' '. $d3['guardian_third_name']),     // Prenoms
            
            'type_identite' =>  $d3['identity_name'] ?? "-",                       // Type Piece Identité
            'numero_identite' =>  $d3['guardian_identity_number'] ?? "-",          // NumeroPieceIdentite
            'address' =>  $d3['guardian_address'] ?? "-",                          // adress
            'profession' => $d3['profession'] ?? "-",                                         // Fonction 
            'contact' => $parent_contact_list ?  (implode(" / ", $parent_contact_list)) : "-",  //  Contact 
            'email' =>  $d3['guardian_email'] ?? "-"                                            // Mail 
        );
        $resp [ 'STATUS' ] = 1;
        return new Response(['body' => json_encode( $resp), 'type' => 'json' ]);
    }



    public function Applications()
    {
        if (isset($this->request->pass[0])) {
            if ($this->request->param('pass')[0] == 'view') 
            {

                $modals = ['commitment-form-modal' => $this->OetScholarship->getModalOptionsScholarship(
                    'view',
                    'Applications', // $this->model,
                    'Scholarship',
                    'Scholarship.view_commitment_form_s',   
                    'KOKORA'
                )];
                $this->set('modals', $modals);
            }
        }
        $csrfToken =  $this->request->param('_csrfToken');
        $this->set('csrfToken', $csrfToken);

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Applications']);
    }
      
    /**
     * When School Validate / Reject A Scholarship Candidate
    */
    public function setScholarshipSchoolValidation(){
        $this->autoRender = false;
        // -------------- Stop If Not Post + Getting POST Data ----------------------------------
        if( !($this->request->is(['post']))) { return false;}
        $p = $this->request->data;

        // -------------- Init  ----------------------------------
        $ScholarshipApplicationsTable = TableRegistry::get('scholarship_applications');
        $response = array(); 

        // -------------- Set Data   ----------------------------------
        $d = [
            'applicant_id' => $p['a_id'],
            'scholarship_id' => $p['s_id'],
            'status_id' => $p['v'],
        ];
        $response [ 'STATUS' ] = -1; 
        $entity_data = $ScholarshipApplicationsTable->newEntity($d);
        if( $ScholarshipApplicationsTable->save($entity_data))
        {
            $response [ 'STATUS' ] = 1; 
        }
        return new Response(['body' => json_encode( $response), 'type' => 'json' ]);
    }
    /**
     * Mass Validation Of Scholarship Eligible Candidates 
    */
    public function setScholarshipSchoolAllValidation(){
        $this->autoRender = false;
        // -------------- Stop If Not Post + Getting POST Data ----------------------------------
        if( !($this->request->is(['post']))) { return false;}
        $p = $this->request->data;

        // -------------- Init  ----------------------------------
        $ScholarshipApplicationsTable = TableRegistry::get('scholarship_applications');
        $response = array(); 

        // -------------- Set Data   ----------------------------------
        $ScholarshipApplicationsTable->updateAll(
            array( 'status_id' => $p['v']),
            array( 'current_institution_id' => $p['c_id'] )
        );
        $response [ 'STATUS' ] = 1; 
        return new Response(['body' => json_encode( $response), 'type' => 'json' ]);
    }
    /**
     * Set Scholarship Custom Config in School Space Like
    */
    public function setScholarshipConfigItem(){
        $this->autoRender = false;
        // -------------- Stop If Not Post + Getting POST Data ----------------------------------
        if( !($this->request->is(['post']))) { return false;}
        $p = $this->request->data;

        // -------------- Init  ----------------------------------
        $ScholarshipParamConfigItemsTable = TableRegistry::get('scholarship_param_config_items');
        $response = array(); 
        
        $session = $this->request->session();
        $userId = $session->read('Auth.User.id');

        // -------------- Set Data   ----------------------------------
        $d = [
            'id' => $p['i'],    // ConfigID         self::SCHOLARSHIP_CONFIGITEM_AVAILABLE_TO_SCHOOLS_ID,
            'code' => $p['c'],  // ConfigCode       self::SCHOLARSHIP_CONFIGITEM_AVAILABLE_TO_SCHOOLS_CODE,
            'value' => $p['v'],
            'modified' => date('Y-m-d H:i:s'),
            'modified_user_id' => $userId
        ];
        $response [ 'STATUS' ] = -1; 
        $entity_data = $ScholarshipParamConfigItemsTable->newEntity($d);
        if( $ScholarshipParamConfigItemsTable->save($entity_data))
        {
            $response [ 'STATUS' ] = 1; 
        }
        return new Response(['body' => json_encode( $response), 'type' => 'json' ]);
    }
    /** <  ------------ #OET  Miché ----------------  */


    /** >  ------------ #OET  Scholarship Dashboard @Miché :: TODO:SET Later Move These parts In a Dedicated Controller -------  */
    public function SummaryScholarshipApplications()
    {
        // Init
        $this->render('sch_dashboard');
        $this->set('ngController', 'ScholarshipsApplicationsDashboardCtrl as ScholarshipsApplicationsDashboardCtrl');
    }
        /**
     * Get Dashboard Data 
    */
    public function getDashboardDataSummaryScholarshipApplications($params=null)
    {
        // Get Params 
        $this->autoRender = false;
        $AcademicPeriodId =  $params; 

        // Var Setting
        $SummaryScholarshipApplicationsTable = TableRegistry::get('summary_scholarship_applications'); 
        $SummaryScholarshipApplications = $SummaryScholarshipApplicationsTable 
             ->find('all')
             ->where(['academic_period_id' => $AcademicPeriodId])
             ->first();

        $r = array(
            "scholarship_param_settings" =>  json_decode($SummaryScholarshipApplications['scholarship_param_settings']) ?? [],
            "scholarship_gen_stats" => json_decode($SummaryScholarshipApplications['scholarship_gen_stats']) ?? [],
        );

        echo json_encode($r);
        die();
    }
    /** <  ------------ #OET   @Miché ----------------  */

    
    /** >  ------------ #OET  ScholarshipAutoGetCandidates @Miché ----------------  */
    /**
     * Analyse All Students During an Academic Period 
     * and retrieve Eligible Scholarship Candidates , Renewal Or Attribution
     */
    public function getScholarshipEligiblesCandidates()
    {
        Log::write('debug', ' GETSCHOLARSHIPELIGIBLESCANDIDATES -  Start :: ');
        set_time_limit(0);
        $this->autoRender = false;
        if( !($this->request->is(['get']))) return false; 

        /** -----------------------------------------------------------------------
         *                         1- GETTING QUERY PARAMETERS 
        * ---------------------------------------------------------------------- */
        $q = array( "v" =>  $this->request->query("v"));

        /** -----------------------------------------------------------------------
         *                         2- SCHOLARSHIP PARAMS  
        * ---------------------------------------------------------------------- */
        $ScholarshipParamSettings = TableRegistry::get('Scholarship.ScholarshipParamSettings'); 
        
        // 2-1- Getting Parameter lists  
        $ScholarshipParamSettingsData = $ScholarshipParamSettings
            ->find('all')
            ->toArray();

        // 2-2- Checking Params Format. If Not Match, We Stop All The Process
        $response = array();
        $param_formats_incorrect  = $this->checkScholarshipParamsFormat($ScholarshipParamSettingsData);
        if($param_formats_incorrect && count($param_formats_incorrect) > 0)  
        {
            $response [ 'STATUS' ] = -1; 
            $response [ 'MSG' ] =  "Ooops, Page `Bourse > Paramétrage`. Erreur de format du paramètre ::";
            $response [ 'ERROR_OBJ' ] = $param_formats_incorrect;
            Log::write('error', ' GETSCHOLARSHIPELIGIBLESCANDIDATES -  $param_formats_incorrect stopping  :: ' . ( json_encode($response) ));
            return new Response(['body' => json_encode( $response), 'type' => 'json']);
            unset($response, $param_formats_incorrect, $ScholarshipParamSettingsData, $ScholarshipParamSettings);
            die();
        }

        // 2-3- Transform Params, Normalize all
        $param_screenshots = array();
        $sp = array();      // Contains all Scholarship Params transformed 
        foreach ($ScholarshipParamSettingsData as $k => $v) {
            $param_screenshots [] = [
                'name' => $v['name'], 
                'code' => $v['code'], 
                'value' => $v['value']
            ];

            if( preg_match( self::SCHOLARSHIP_REGEX_LIST['INT'],  $v['value']) )
            {
                $sp[ $v['code'] ] = array( 'NAME'=> $v['name'], 'VALUE' =>  (int) $v['value'] );
            } 

            if( preg_match( self::SCHOLARSHIP_REGEX_LIST['FLOAT'],  $v['value']) )
            {
                $sp[ $v['code'] ] = array( 'NAME'=> $v['name'], 'VALUE' =>  (float) $v['value'] );
            } 

            if($v['code'] == self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C)
            {
                [$sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MIN ],  $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MAX ]] = explode("-", $v['value']);

                $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MIN ] =  array( 'NAME'=> self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MIN, 'VALUE' =>  (float) $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MIN ] );
                $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MAX ] =  array( 'NAME'=> self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MAX, 'VALUE' =>  (float) $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MAX ] );
                continue;
            } 
            if($v['code'] == self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C)
            {
                [$sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MIN ],  $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MAX ]] = explode("-", $v['value']);

                $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MIN ] =  array( 'NAME'=> self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MIN, 'VALUE' =>  (float) $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MIN ] );
                $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MAX ] =  array( 'NAME'=> self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MAX, 'VALUE' =>  (float) $sp[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MAX ] );
                continue;
            }
            
            if( $v['code'] == self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ALLOUE || 
                $v['code'] == self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1  || 
                $v['code'] == self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 )
            {
                $sp[ $v['code'] ] = array( 'NAME'=> $v['name'], 'VALUE' =>  (int) (preg_replace( self::SCHOLARSHIP_REGEX_LIST['AMOUNT_NORMALIZE'], '' ,$v['value'] )) );
            }
        }
        $SCHOLARSHIP_PARAMS_TRANSFORMED = $sp;

        $SCHOLARSHIP_CANDIDATES_STATS = array(
            "_" => array(
                "ADJUSTING" => array(
                    "2ndeN0" => 0,  // Initial Number Of 2nde Students getting a scholarship : Theorique
                    "2ndeN1" => 0,  // Number of 2nde Students that will get Scholarship really after Adjustment
                    "NbLoop" => 0,  // Number Of Loop from  (2ndeN0 -> 2ndeN1)
                )
            ),
            "GLOBAL" => array(
                    "NB_RENEWAL" => 0,            // Total Number of Student checked for Scholarship  (Renewal : Full + Partial)
                    "NB_ATTRIBUTION" => 0,        // Total Number of Student checked for Scholarship  (Attribution: Full Only )
                    "NB_1_BOURSIER" => 0,         // Total Number of Student getting a full scholarship (Attribution::FullScholarShip + Renewal::FullScholarShip)
                    "NB_05_BOURSIER" => 0,        // Total Number of Student getting a partial scholarship (Renewal::PartialScholarship)
            ),
            "RENEWAL" => array(
                    "NB_1_BOURSIER" => 0,              // Total Number Of Student getting a full scholarship : Cycle 1 + Cycle 2
                    "NB_05_BOURSIER" => 0,             // Total Number Of Student getting a partial scholarship : Cycle 1 + Cycle 2
                    "AMOUNT_1_BOURSIER" => 0,          // Overall Money allocated for renewal full ScholarShip : : Cycle 1 + Cycle 2
                    "AMOUNT_05_BOURSIER" => 0,         // Overall Money allocated for renewal partial ScholarShip : : Cycle 1 + Cycle 2
                    "AMOUNT_ALL_105_BOURSIER" => 0,    // Overall Money allocated for renewal ScholarShip :: ( Partial + Full )
                    "1e_CYCLE" => array(        
                        "NB_1_BOURSIER" => 0,          // Total Number Of Student getting a full scholarship :: 1er CYCLE
                        "NB_05_BOURSIER" => 0,         // Total Number Of Student getting a partial scholarship :: 1er CYCLE
                        "AMOUNT_1_BOURSIER" => 0,      // Overall Money allocated for renewal full ScholarShip : Cycle 1
                        "AMOUNT_05_BOURSIER" => 0,     // Overall Money allocated for renewal partial ScholarShip : Cycle 2
                    ),
                    "2e_CYCLE" => array(        
                        "NB_1_BOURSIER" => 0,          // Total Number Of Student getting a full scholarship :: 2e CYCLE
                        "NB_05_BOURSIER" => 0,         // Total Number Of Student getting a partial scholarship :: 2e CYCLE
                        "AMOUNT_1_BOURSIER" => 0,      // Overall Money allocated for renewal full ScholarShip : Cycle 1
                        "AMOUNT_05_BOURSIER" => 0,     // Overall Money allocated for renewal partial ScholarShip : Cycle 2
                    ),
                    "PER_LEVEL" => array(               // (Containing Number Of Candidates Per Level |  5e:23,4e:24) 
                        '1_BOURSIER' => array(),  
                        '05_BOURSIER' => array(),
                    )
            ),
            "ATTRIBUTION" => array(
                        "MARK_CM2_TGP" => 0,                        // Computed :: Mark to get the scholarship 
                        "MARK_3e_MO" => 0,                          // Computed :: Mark to get the scholarship 
                        "NB_1_BOURSIER" => 0,                       // Total Number Of Student getting a new full scholarship 
                        "AMOUNT_THEORIQUE_ALL_1_BOURSIER" => 0,     // Gross Overall Money allocated for attribution full ScholarShip : 6e+2ndeX [MontantGlobal-Renewal]
                        "AMOUNT_THEORIQUE_NET_BOURSIER" => 0,       // Net Overall Money Allocated for attribution full ScholarShip : 6e+2ndeX  (After Adjustment / Related to Gross Amount provided to DOB)
                        "AMOUNT_REEL_NET_BOURSIER" => 0,            // Net Overall Money Allocated for attribution full ScholarShip : 6e+2ndeX (Related to DECO Results)
                        "AMOUNT_THEORIQUE_REMAINING" => 0,          // Little Remaining after deduction  (AMOUNT_ALL_1_BOURSIER - AMOUNT_THEORIQUE_NET_BOURSIER) (Related to Gross Amount provided to DOB)
                        "AMOUNT_REEL_REMAINING" => 0,               // Little Remaining after deduction (Related to Real Number Of Students succeded the exam, After Deduction )
                "6e" => array(        
                        "PROPORTION_REELLE" => 0,                   // Proportion Reelle Attribution 6e 
                        "AMOUNT_1_BOURSIER_THEORIQUE" => 0,         // Overall Money allocated for attribution full ScholarShip :: 1er CYCLE / 6è 
                        "AMOUNT_1_BOURSIER_REEL" => 0,              // Overall Money allocated for attribution full ScholarShip :: 1er CYCLE / 6è  (Based Of DECO Results)
                        "NB_1_BOURSIER_THEORIQUE" => 0,             // Total Number Of Student getting a new full scholarship :: 1er CYCLE / 6è
                        "NB_1_BOURSIER_REEL" => 0,                  // Total Number Of Student getting a new full scholarship :: 1er CYCLE / 6è (Based Of DECO Results)
                ),
                "2nde" => array(  
                        "PROPORTION_REELLE" => 0,                   // Proportion Reelle Attribution 2nde
                        "AMOUNT_1_BOURSIER_THEORIQUE" => 0,         // Overall Money allocated for attribution full ScholarShip :: 1er CYCLE / 6è 
                        "AMOUNT_1_BOURSIER_REEL" => 0,              // Overall Money allocated for attribution full ScholarShip :: 1er CYCLE / 6è  (Based Of DECO Results)
                        "NB_1_BOURSIER_THEORIQUE" => 0,             // Total Number Of Student getting a new full scholarship :: 2e CYCLE / 2nde
                        "NB_1_BOURSIER_REEL" => 0,                  // Total Number Of Student getting a new full scholarship :: 1er CYCLE / 6è (Based Of DECO Results)
                ),
                "PER_LEVEL" => array(                               // (Containing Number Of Candidates Per Level |  5e:23,4e:24) 
                    '1_BOURSIER' => array()
                )
            )
        );
        // 3...

        /** -----------------------------------------------------------------------
         *                         4- SCHOLARSHIPS RENEWAL 
        * ---------------------------------------------------------------------- */
        // 4-0 Getting Scholarship ELIGIBLE Worflow step ID +  Assignee ID 
        $WorkflowStepsTable = TableRegistry::get('workflow_steps'); 
        $UsersTable = TableRegistry::get('security_users'); 
        $InstitutionStudentsTable = TableRegistry::get('institution_students'); 

        $workflowEligibleStepIDQuery =  $WorkflowStepsTable 
                    ->find()
                    ->select([
                        'id'=> $WorkflowStepsTable->aliasField('id')
                    ])
                    ->where([
                        $WorkflowStepsTable->aliasField('code_national') => self::SCHOLARSHIP_WORKFLOW_STEP_CODE['ELIGIBLE'],
                        $WorkflowStepsTable->aliasField('workflow_id') => self::SCHOLARSHIP_WORKFLOW_ID 
                    ])
                    ->first()
                    ->toArray();
        
        $workflowEligibleAssigneeIDQuery = $UsersTable 
                    ->find()
                    ->select([
                        'id'=> $UsersTable->aliasField('id')
                    ])
                    ->where([
                        $UsersTable->aliasField('openemis_no') => $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__ELIGIBLE_STEP_GLOBAL_ASSIGNEE ] ["VALUE"],
                    ])
                    ->first()
                    ->toArray();
    

        try {
            $workflowEligibleStepID =  $workflowEligibleStepIDQuery['id'];
            $workflowEligibleStepAssigneeID =  $workflowEligibleAssigneeIDQuery['id'];
        } catch (\Throwable $th) {
            $response [ 'STATUS' ] = -2; 
            $response [ 'MSG' ] = "Ooops, Erreur :: Il faudrait revoir la valeur du paramètre de bourse `WORKLOW_INE_VALIDATEUR_GLOBAL_ETAPE_ELIGIBLE` ou de l'étape du workflow `ELIGIBLE`"; 
            $response [ 'ERROR_OBJ' ] = [];
            Log::write('error', ' GETSCHOLARSHIPELIGIBLESCANDIDATES - Workflow (ELIGIBLE STEP | Assignee ID)  not found in Workflow Steps List with code_national="DOB_ELIGIBLE" :: '.(json_encode($response)));
            return new Response(['body' => json_encode( $response), 'type' => 'json' ]);
            unset($response, $ScholarshipParamSettingsData, $workflowEligibleStepIDQuery, $workflowEligibleAssigneeIDQuery);
            die();
        }
        
        // 4-1 Getting Current Academic , Corresponding Scholarship :: { PreviousAcademicPeriod + CurrentAcademicPeriod } 
        // + 4-2 Get Previous Academic Data 
        // + 4-3 Get CIV Nationality ID 
        $Scholarships = TableRegistry::get('Scholarship.Scholarships');
        $CurrentScholarshipData  = $Scholarships
            ->find()
            ->select([
                'id' => $Scholarships->aliasField('id'),
                'code' => $Scholarships->aliasField('code'),
                'name' => $Scholarships->aliasField('name'),
                'current_academic_period_id' => $Scholarships->aliasField('academic_period_id'),
                'previous_academic_period_id' => $Scholarships->aliasField('previous_academic_period_id'),
                'total_amount' => $Scholarships->aliasField('total_amount')
            ])
            ->contain('AcademicPeriods')
            ->where([
                'AcademicPeriods.current' => 1,
                $Scholarships->aliasField('scholarship_financial_assistance_type_id') => self::SCHOLARSHIP_CI_DOB_TYPE_ID
            ])
            ->first()
            ->toArray();

        if(empty($CurrentScholarshipData)){
            $response [ 'STATUS' ] = -3; 
            $response [ 'MSG' ] = "Ooops, Pas de bourse correspondante à la période académique en cours dans la partie `Bourse > Details`"; 
            $response [ 'ERROR_OBJ' ] = [];
            Log::write('error', ' GETSCHOLARSHIPELIGIBLESCANDIDATES - Pas de bourse actuelle correspondante dans la partie `Bourse > Details` :: '.(json_encode($response)));
            return new Response(['body' => json_encode( $response), 'type' => 'json' ]);
            unset($response, $CurrentScholarshipData);
            die();
        }
        $scholarship_current_academic_period_id = $CurrentScholarshipData['current_academic_period_id'];
        $scholarship_previous_academic_period_id = $CurrentScholarshipData['previous_academic_period_id'];
        $scholarship_current_id = $CurrentScholarshipData['id'];

        // 4-4 - Compute Eligible Students Number for Renewals (N) with {STATUT=AFFECTE; NATIONALITY=IVOIRIENNE; CLASS = [5e,4e,3e,  1ereA,1ereC,1ereD,  TleA,TleC,TleD], AcademicPeriod  }
        // 4-5 + Script   Round Number 
        $education_grade_codes_renewal =  array_values( self::SCHOLARSHIP_EDUCATION_GRADES_CODE_RENEWALS );

        $TS_AVG_List = TableRegistry::get('institution_students_report_cards_avg');
        $ScholarshipEligibleCandidates_Ls_Query =  $TS_AVG_List
            ->find()
            ->where([ 
                'nationality' => self::SCHOLARSHIP_CIV_NATIONALITY_ID ,                 // NATIONALITY=IVOIRIENNE
                'affectation' => self::SCHOLARSHIP_STUDENT_AFFECTE_ID,                  // STATUT=AFFECTE
                'education_grade_code IN ' => $education_grade_codes_renewal,           // CLASS = [5e,4e,3e,  1ereA,1ereC,1ereD,  TleA,TleC,TleD] }
                'academic_period_id ' => $scholarship_previous_academic_period_id      // Corresponding to Previous Academic Period 
            ]);

        $ScholarshipEligibleCandidates_Nb = $ScholarshipEligibleCandidates_Ls_Query->count();

        $t = ceil( $ScholarshipEligibleCandidates_Nb / self::SCHOLARSHIP_CHUNCK_LIMIT);
        
        Log::write('debug', ' GETSCHOLARSHIPELIGIBLESCANDIDATES - $t:: '.  ($t). '  $Nb::'. $ScholarshipEligibleCandidates_Nb .' '.(self::SCHOLARSHIP_CHUNCK_LIMIT));
        
        // 4-6 Getting Eligible Candidates list :: (Boursiers, 1/2 Boursiers) & Stats(Vol, Nb, AmountRenewals) +
        // + 4-7 Execute Batch Insertion Query 
        // + 4-8 End Of Batch n (#N/1000 ) processing ; GoBack To 4-6

        $ScholarshipApplicationsTmp  = TableRegistry::get('scholarship_applications_tmp');
        $sql_connection = ConnectionManager::get('default');
        try {
            $sql_connection->execute('TRUNCATE scholarship_applications_tmp');  
        } catch (\Throwable $th) {
            $response [ 'STATUS' ] = -4; 
            $response [ 'MSG' ] = "Ooops, Erreur lors du nettoyage de la table scholarship_applications_tmp. "; 
            $response [ 'ERROR_OBJ' ] = ["ERROR_CODE"=> "M05072024k1355002"];
            Log::write('error', ' GETSCHOLARSHIPELIGIBLESCANDIDATES - '.($response [ 'MSG' ]).' :: '.(json_encode($response)));
            return new Response(['body' => json_encode( $response), 'type' => 'json' ]);
            unset($response, $ScholarshipApplicationsTmp, $sql_connection);
            die();
        }
        
        $i = 0;
        $q_limit = self::SCHOLARSHIP_CHUNCK_LIMIT;
        $q_offset = 0;
        $ELIGIBLE_CANDIDATES_RENEWAL_LIST = [];
        while ($i <= $t) {
            $ELIGIBLE_CANDIDATES_RENEWAL_LIST = [];

            // 4-6 Getting Eligible Candidates list :: (Boursiers, 1/2 Boursiers) & Stats(Vol, Nb, AmountRenewals) +
            $ScholarshipEligibleCandidates_Ls = $ScholarshipEligibleCandidates_Ls_Query
                ->limit($q_limit)
                ->offset($q_offset)
                ->toArray();

            if( empty($ScholarshipEligibleCandidates_Ls)) 
                break;

            foreach ($ScholarshipEligibleCandidates_Ls as $k => $v) {
                // Get Student Current School  | -1 If Student Not In Any School in the system for current academic period 
                $student_current_school_id_q = $InstitutionStudentsTable
                    ->find()
                    ->select(['institution_id'])
                    ->where([
                        'student_id' => $v['student_id'],
                        'academic_period_id' => $scholarship_current_academic_period_id
                    ])
                    ->first()
                    ->toArray();
    
                $student_current_school_id = $student_current_school_id_q ['institution_id'] ?? -1;

                // Build Object 
                $boursier_student = array(
                    '1_BOURSIER' => $this->buildEligibleCandidatesApplicationsTableEntity( 
                                        $v['student_id'],
                                        $scholarship_current_id,
                                        $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"],
                                        $workflowEligibleStepID,
                                        $workflowEligibleStepAssigneeID,
                                        self::SCHOLARSHIP_BOURSIER_CATEGORY['RENOUVELLEMENT'],
                                        self::SCHOLARSHIP_BOURSIER_LOT['1BOURSIER'],
                                        $student_current_school_id
                    ),
                    '05_BOURSIER' => $this->buildEligibleCandidatesApplicationsTableEntity( 
                                        $v['student_id'],
                                        $scholarship_current_id,
                                        $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 ] ["VALUE"],
                                        $workflowEligibleStepID,
                                        $workflowEligibleStepAssigneeID,
                                        self::SCHOLARSHIP_BOURSIER_CATEGORY['RENOUVELLEMENT'],
                                        self::SCHOLARSHIP_BOURSIER_LOT['05_BOURSIER'],
                                        $student_current_school_id
                        )
                );

                if( in_array($v['education_grade_code'], self::SCHOLARSHIP_EDUCATION_GRADES_CODE_1CYCLE) )                           // 1er Cycle
                {
                    if( $v['avg_mark'] >= $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_B_1_1C ] ["VALUE"] )     // BOURSIER
                    {
                        $ELIGIBLE_CANDIDATES_RENEWAL_LIST[] = $boursier_student ['1_BOURSIER'];
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['NB_1_BOURSIER'] += 1;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_1_BOURSIER'] +=  $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['1e_CYCLE'] ['NB_1_BOURSIER'] += 1; 
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['1e_CYCLE'] ['AMOUNT_1_BOURSIER'] += $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['1_BOURSIER'] [ $v['education_grade_code'] ] = $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['1_BOURSIER'] [ $v['education_grade_code'] ] ?? 0;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['1_BOURSIER'] [ $v['education_grade_code'] ] += 1;

                    } else if ( 
                        $v['avg_mark'] >= $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MIN ] ["VALUE"] && // 1/2 BOURSIER
                        $v['avg_mark'] <= $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_1C_MAX ] ["VALUE"] ) 
                    {
                        $ELIGIBLE_CANDIDATES_RENEWAL_LIST[] = $boursier_student ['05_BOURSIER'];
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['NB_05_BOURSIER'] += 1;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_05_BOURSIER'] +=  $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['1e_CYCLE'] ['NB_05_BOURSIER'] += 1; 
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['1e_CYCLE'] ['AMOUNT_05_BOURSIER'] += $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['05_BOURSIER'] [ $v['education_grade_code'] ] = $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['05_BOURSIER'] [ $v['education_grade_code'] ]  ?? 0;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['05_BOURSIER'] [ $v['education_grade_code'] ] += 1;
                    }

                } else if ( in_array($v['education_grade_code'], self::SCHOLARSHIP_EDUCATION_GRADES_CODE_2CYCLE) )                  // 2nde Cycle
                {
                    if(  $v['avg_mark'] >= $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_B_2_2C] ["VALUE"] )     // BOURSIER
                    {
                        $ELIGIBLE_CANDIDATES_RENEWAL_LIST[] = $boursier_student ['1_BOURSIER'];
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['NB_1_BOURSIER'] += 1;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_1_BOURSIER'] += $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['2e_CYCLE'] ['NB_1_BOURSIER'] += 1; 
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['2e_CYCLE'] ['AMOUNT_1_BOURSIER'] += $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['1_BOURSIER'] [ $v['education_grade_code'] ]  =  $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['1_BOURSIER'] [ $v['education_grade_code'] ] ?? 0;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['1_BOURSIER'] [ $v['education_grade_code'] ]  +=  1;

                    } else if ( 
                        $v['avg_mark'] >= $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MIN ] ["VALUE"] && // 1/2 BOURSIER
                        $v['avg_mark'] <= $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_B_05_2C_MAX ] ["VALUE"] ) 
                    {
                        $ELIGIBLE_CANDIDATES_RENEWAL_LIST[] = $boursier_student ['05_BOURSIER'];
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['NB_05_BOURSIER'] += 1;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_05_BOURSIER'] +=  $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['2e_CYCLE'] ['NB_05_BOURSIER'] += 1; 
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['2e_CYCLE'] ['AMOUNT_05_BOURSIER'] += $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_05 ] ["VALUE"] ;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['05_BOURSIER'] [ $v['education_grade_code'] ]  =  $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['05_BOURSIER'] [ $v['education_grade_code'] ]  ?? 0;
                        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['PER_LEVEL'] ['05_BOURSIER'] [ $v['education_grade_code'] ]  +=  1;
                    }
                }
            }

            // + 4-7 Execute Batch Insertion Query 
            // + 4-8 End Of Batch n (#N/1000 ) processing ; GoBack To 4-6
                        // foreach ($ELIGIBLE_CANDIDATES_RENEWAL_LIST as $v) { $e = $ScholarshipApplicationsTmp->newEntities()}
            $entities_list = $ScholarshipApplicationsTmp->newEntities($ELIGIBLE_CANDIDATES_RENEWAL_LIST);
            $ScholarshipApplicationsTmp->saveMany($entities_list);

            $q_offset += $q_limit;
            $i += 1;
        }

        // 4-9 Generate Stats   :: Volume Attribution , Nb Boursier (1, 2 Cycle), Nb 1/2Boursier (1, 2 Cycle), Nb Boursier Par Niveau(5e,4e,3e,...), Nb 1/2Boursier Par Niveau(5e,4e,3e,...) .. 
        // Done ... Runtime 
        $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_ALL_105_BOURSIER'] = ($SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_1_BOURSIER'] + $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_05_BOURSIER']);
        unset($ELIGIBLE_CANDIDATES_RENEWAL_LIST, $ScholarshipEligibleCandidates_Ls_Query, $ScholarshipEligibleCandidates_Ls);

        /** -----------------------------------------------------------------------
         *                         5- SCHOLARSHIPS ATTRIBUTION  
        * ---------------------------------------------------------------------- */
        $ExaminationResultsDECO = TableRegistry::get('examinations_results_deco');

        $PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E = $SCHOLARSHIP_PARAMS_TRANSFORMED [ self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E] && 
                                                preg_match( self::SCHOLARSHIP_REGEX_LIST['INT'], $SCHOLARSHIP_PARAMS_TRANSFORMED [ self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E]['VALUE'] ) ?
                                                (int) $SCHOLARSHIP_PARAMS_TRANSFORMED [ self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E]['VALUE']: 
                                                null;
        $PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE = $SCHOLARSHIP_PARAMS_TRANSFORMED [ self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE] && 
                                                preg_match( self::SCHOLARSHIP_REGEX_LIST['INT'], $SCHOLARSHIP_PARAMS_TRANSFORMED [ self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE]['VALUE']) ?
                                                (int) $SCHOLARSHIP_PARAMS_TRANSFORMED [ self::SCHOLARSHIP_PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE]['VALUE']: 
                                                null;
        
        // 4-1 Compute Overall Attribution Volume       : (AmountAttributions = #BOURSE_MONTANT_ALLOUE -  #AmountRenewals)
        $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['AMOUNT_THEORIQUE_ALL_1_BOURSIER'] = ($SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ALLOUE ] ["VALUE"] - $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['AMOUNT_ALL_105_BOURSIER']);
        
        // 4-2 Compute Attribution Volume for 1erCycle  : (AmountAttributionsC1 = #BOURSE_LOT_ATTRIBUTION_1C * AmountAttributions)
        // + 4-3 Compute Attribution Number for 1erCycle  : (NbAttributionsC1 = #AmountAttributionsC1 / #BOURSE_MONTANT_ELEVE_1)
        $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['6e'] ['PROPORTION_REELLE'] = ($SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_1C ] ["VALUE"]);
        $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['6e'] ['AMOUNT_1_BOURSIER_THEORIQUE'] = round( ($SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_1C ] ["VALUE"] * $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['AMOUNT_THEORIQUE_ALL_1_BOURSIER']), 2);
        $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['6e'] ['NB_1_BOURSIER_THEORIQUE'] =  ceil( $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['6e'] ['AMOUNT_1_BOURSIER_THEORIQUE'] /  $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"]);
        
        // 4-3 Compute Attribution Volume for 2èCycle   : (AmountAttributionsC2 = #BOURSE_LOT_ATTRIBUTION_2C * AmountAttributions)
        // + 4-4 Compute Attribution Number for 2èCycle   : (NbAttributionsC2 = #AmountAttributionsC2 / #BOURSE_MONTANT_ELEVE_1)
        $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['PROPORTION_REELLE'] =  (1 - $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_LOT_ATTRIBUTION_1C ] ["VALUE"]);
        $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['AMOUNT_1_BOURSIER_THEORIQUE'] =  ( $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['AMOUNT_THEORIQUE_ALL_1_BOURSIER'] - $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['6e'] ['AMOUNT_1_BOURSIER_THEORIQUE']);
        $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['NB_1_BOURSIER_THEORIQUE'] =  ceil( $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['AMOUNT_1_BOURSIER_THEORIQUE'] /  $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"]);
        
        // 4-5 Adjust Nb   : Y = AX + BY {Y:OverallBudget , A & B:AssignedAmountPerStudent (Currently A=B)  X:NbStudent1Cycle Y:NbStudent2Cycle} | As We need only integers && No 1/2Person 🫣)
        $nbBousiers2nde0 = $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['NB_1_BOURSIER_THEORIQUE'];
        $nbBousiers6e0  = $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['6e'] ['NB_1_BOURSIER_THEORIQUE'];
        $s0 = $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ALLOUE ] ["VALUE"];
        $s1 = ($SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] * $nbBousiers6e0) + 
              ($SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] * $nbBousiers2nde0);
        if( $s1 > $s0)
        {
            $n =  $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['NB_1_BOURSIER_THEORIQUE'];
            $l = $r = 0;
            while ($s1 > $s0 ) {
                ++$l;
                $n--;
                $s1 =   ($SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] * $nbBousiers6e0) + 
                        ($SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"] *  $n);
                $r = $s0 - $s1;
            }

            $SCHOLARSHIP_CANDIDATES_STATS ["ATTRIBUTION"]["AMOUNT_THEORIQUE_NET_BOURSIER"] = $s1;
            $SCHOLARSHIP_CANDIDATES_STATS ["ATTRIBUTION"]["AMOUNT_THEORIQUE_REMAINING"] = $r;
            $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['NB_1_BOURSIER_THEORIQUE'] = $n;

            $SCHOLARSHIP_CANDIDATES_STATS ['_']["ADJUSTING"]['2ndeN0'] = $nbBousiers2nde0;
            $SCHOLARSHIP_CANDIDATES_STATS ['_']["ADJUSTING"]['2ndeN1'] = $n;
            $SCHOLARSHIP_CANDIDATES_STATS ['_']["ADJUSTING"]['NbLoop'] = $l;
        }

        // 4-6 ------------- Retrieve Scholarship Eligibles Candidates : CM2->6è (STATUT=AFFECTE, NAT=IVOIRIENNE, AGE?, TGP | MO) ---------------------------------------
        $q_limit = self::SCHOLARSHIP_CHUNCK_LIMIT;
        $ELIGIBLE_CANDIDATES_6E_LIST = [];

            // 4-6_a Build Query & Get 
        $q_where = [];
        $q_where['nationality'] = self::SCHOLARSHIP_CIV_NATIONALITY_ID;     // NATIONALITY=IVOIRIENNE
        $q_where['affectation'] = self::SCHOLARSHIP_STUDENT_AFFECTE_ID;     // STATUT=AFFECTE
        $q_where['exam_type'] = self::SCHOLARSHIP_DECO_EXAM_TYPE_ID['CEPE'];       
        $q_where['exam_academic_period_id'] = $scholarship_previous_academic_period_id;
        if($PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E)
        {
            $q_where['student_current_age <= '] = $PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_6E;
        }

        $ELIGIBLE_CANDIDATES_6E_LIST = $ExaminationResultsDECO
            ->find()
            ->where($q_where)
            ->orderDesc('exam_total_mark')
            ->limit( $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['6e'] ['NB_1_BOURSIER_THEORIQUE'] )
            ->toArray();
    
            
        if($ELIGIBLE_CANDIDATES_6E_LIST)
        {
             
            // 4-6_b Generate the lowest mark to be scholarship candidates & the real number of Candidates : 6e 
            $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['6e']['NB_1_BOURSIER_REEL'] = count($ELIGIBLE_CANDIDATES_6E_LIST);
            $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['6e']['AMOUNT_1_BOURSIER_REEL'] = ($SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['6e']['NB_1_BOURSIER_REEL'] * $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"]);
            $ki = $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['6e']['NB_1_BOURSIER_REEL'] - 1;
            $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['MARK_CM2_TGP'] = $ELIGIBLE_CANDIDATES_6E_LIST [ $ki ] ['exam_total_mark'] ;
            
                // 4-6_c Building  Entities List 
            foreach ($ELIGIBLE_CANDIDATES_6E_LIST as $k => $v) {
                // Get Student Current School  | -1 If Student Not In Any School in the system for current academic period 
                $student_current_school_id_q =  $InstitutionStudentsTable
                    ->find()
                    ->select(['institution_id'])
                    ->where([
                        'student_id' => $v['student_id'],
                        'academic_period_id' => $scholarship_current_academic_period_id
                    ])
                    ->first()
                    ->toArray();
                
                $student_current_school_id = $student_current_school_id_q ['institution_id'] ?? -1;

                // Build Object 
                $boursier_student = array(
                    '1_BOURSIER' => $this->buildEligibleCandidatesApplicationsTableEntity( 
                        $v['student_id'],
                        $scholarship_current_id,
                        $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"],
                        $workflowEligibleStepID,
                        $workflowEligibleStepAssigneeID,
                        self::SCHOLARSHIP_BOURSIER_CATEGORY['ATTRIBUTION'],
                        self::SCHOLARSHIP_BOURSIER_LOT['1BOURSIER'],
                        $student_current_school_id
                    )
                );
                $ELIGIBLE_CANDIDATES_6E_LIST[$k] = $boursier_student ['1_BOURSIER'];
            }
                // 4-6_d Insert data 
            $entities_list = $ScholarshipApplicationsTmp->newEntities( $ELIGIBLE_CANDIDATES_6E_LIST );
            $ScholarshipApplicationsTmp->saveMany($entities_list);
        }
        unset($ELIGIBLE_CANDIDATES_6E_LIST);

        // 4-7 -------------  Retrieve Scholarship Eligibles Candidates : 3è->(2ndeA|2ndeC) ------------------------------------------------------------------------------
        $q_limit = self::SCHOLARSHIP_CHUNCK_LIMIT;
        $ELIGIBLE_CANDIDATES_2NDE_LIST = [];

            // 4-7_a Build Query & Get  ( & $q_where :: From 6è)
        $q_where['exam_type'] = self::SCHOLARSHIP_DECO_EXAM_TYPE_ID['BEPC'];       
        if($PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE)
        {
            $q_where['student_current_age <= '] = $PARAM__AGE_MAXI_ATTRIBUTION_BOURSE_2NDE;
        }

        $ELIGIBLE_CANDIDATES_2NDE_LIST = $ExaminationResultsDECO
            ->find()
            ->where($q_where)
            ->orderDesc('exam_total_mark')
            ->limit( $SCHOLARSHIP_CANDIDATES_STATS ['ATTRIBUTION'] ['2nde'] ['NB_1_BOURSIER_THEORIQUE'] )
            ->toArray();

        if($ELIGIBLE_CANDIDATES_2NDE_LIST)
        {
                // 4-7_b Generate the lowest mark to be scholarship candidates & the real number of Candidates : 2nde
            $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['2nde']['NB_1_BOURSIER_REEL'] = count( $ELIGIBLE_CANDIDATES_2NDE_LIST );
            $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['2nde']['AMOUNT_1_BOURSIER_REEL'] = ($SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['2nde']['NB_1_BOURSIER_REEL'] * $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"]);
            $ki = $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['2nde']['NB_1_BOURSIER_REEL'] - 1;
            $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['MARK_3e_MO'] = $ELIGIBLE_CANDIDATES_2NDE_LIST [ $ki ]['exam_total_mark'] ;
        
                // 4-7_c Building  Entities List 
            foreach ( $ELIGIBLE_CANDIDATES_2NDE_LIST as $k => $v) {
                // Get Student Current School  | -1 If Student Not In Any School in the system for current academic period 
                $student_current_school_id_q =  $InstitutionStudentsTable
                    ->find()
                    ->select(['institution_id'])
                    ->where([
                        'student_id' => $v['student_id'],
                        'academic_period_id' => $scholarship_current_academic_period_id
                    ])
                    ->first()
                    ->toArray();
    
                $student_current_school_id = $student_current_school_id_q ['institution_id'] ?? -1;

                // Build Object
                $boursier_student = array(
                    '1_BOURSIER' => $this->buildEligibleCandidatesApplicationsTableEntity( 
                        $v['student_id'],
                        $scholarship_current_id,
                        $SCHOLARSHIP_PARAMS_TRANSFORMED[ self::SCHOLARSHIP_PARAM__BOURSE_MONTANT_ELEVE_1 ] ["VALUE"],
                        $workflowEligibleStepID,
                        $workflowEligibleStepAssigneeID,
                        self::SCHOLARSHIP_BOURSIER_CATEGORY['ATTRIBUTION'],
                        self::SCHOLARSHIP_BOURSIER_LOT['1BOURSIER'],
                        $student_current_school_id 
                    )
                );
                $ELIGIBLE_CANDIDATES_2NDE_LIST[$k] = $boursier_student ['1_BOURSIER'];
            }
                // 4-7_d Insert data 
            $entities_list = $ScholarshipApplicationsTmp->newEntities( $ELIGIBLE_CANDIDATES_2NDE_LIST );
            $ScholarshipApplicationsTmp->saveMany($entities_list);
        }
        unset($ELIGIBLE_CANDIDATES_2NDE_LIST);

        $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['AMOUNT_REEL_NET_BOURSIER'] = ($SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['6e']['AMOUNT_1_BOURSIER_REEL'] + $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['2nde']['AMOUNT_1_BOURSIER_REEL']);
  
        $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['NB_1_BOURSIER'] = ($SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['6e']['NB_1_BOURSIER_REEL']  + $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['2nde']['NB_1_BOURSIER_REEL']);
        $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['PER_LEVEL'][ (self::SCHOLARSHIP_EDUCATION_GRADES_CODE_ATTRIBUTIONS['6e']) ] = $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['6e']['NB_1_BOURSIER_REEL'];
        $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['PER_LEVEL'][ (self::SCHOLARSHIP_EDUCATION_GRADES_CODE_ATTRIBUTIONS['2nde']) ] = $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['2nde']['NB_1_BOURSIER_REEL'];
        $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['AMOUNT_REEL_REMAINING'] = ($SCHOLARSHIP_CANDIDATES_STATS ["ATTRIBUTION"]["AMOUNT_THEORIQUE_NET_BOURSIER"] - $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['AMOUNT_REEL_NET_BOURSIER']);
        
        $SCHOLARSHIP_CANDIDATES_STATS['GLOBAL']['NB_ATTRIBUTION'] = $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['NB_1_BOURSIER'];
        $SCHOLARSHIP_CANDIDATES_STATS['GLOBAL']['NB_1_BOURSIER'] = ($SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['NB_1_BOURSIER'] + $SCHOLARSHIP_CANDIDATES_STATS['RENEWAL']['NB_1_BOURSIER']);
        $SCHOLARSHIP_CANDIDATES_STATS['GLOBAL']['NB_05_BOURSIER'] = $SCHOLARSHIP_CANDIDATES_STATS['RENEWAL']['NB_05_BOURSIER'];
        $SCHOLARSHIP_CANDIDATES_STATS ['GLOBAL'] ['NB_RENEWAL'] = $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['NB_1_BOURSIER'] + $SCHOLARSHIP_CANDIDATES_STATS ['RENEWAL'] ['NB_05_BOURSIER'];

        /** -----------------------------------------------------------------------------------------------------
         *              6- COMPLETION BY MOVING ALL DATA FROM TMP TO REAL TABLES 
         *   --- Loop and Insert Data In Table | Number of Eligible Candidates will be always <= $t  ---
        * ----------------------------------------------------------------------------------------------------- */
        $sql_connection->execute('TRUNCATE scholarship_applications');
        $i = 0;
        $q_offset = 0;
        while ($i <= $t) {
            $q = "INSERT INTO scholarship_applications SELECT * FROM scholarship_applications_tmp LIMIT ". $q_limit  ." OFFSET ".$q_offset;
            $sql_connection->execute($q);

            $q_offset += $q_limit;
            $i++;
        }
        $sql_connection->execute('TRUNCATE scholarship_applications_tmp');

        /** ---------------------------------------------------------------------------------------------------------------
         *              7- REFRESH PARAMS (MO+TGP) with Generated + SAVE PARAMS + RESULTS FOR DASHBOARD 
        * ---------------------------------------------------------------------- ---------------------------------------- */
        $session = $this->request->session();
        $userId = $session->read('Auth.User.id');

        $k_TGP_object =  array_filter($param_screenshots, function($v){
            return $v['code'] == self::SCHOLARSHIP_PARAM__BOURSE_TGP;
        });
        $k_MO_object =  array_filter($param_screenshots, function($v){
            return $v['code'] == self::SCHOLARSHIP_PARAM__BOURSE_MO;
        });
        $param_screenshots[  ( array_key_first($k_TGP_object)) ]['value'] =  $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['MARK_CM2_TGP'];
        $param_screenshots[  ( array_key_first($k_TGP_object)) ]['name'] .= ' (**)';
        $param_screenshots[  ( array_key_first($k_MO_object)) ]['value'] =  $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['MARK_3e_MO'];
        $param_screenshots[  ( array_key_first($k_MO_object)) ]['name'] .=  '(**)';

        $SummaryScholarshipApplicationsTable = TableRegistry::get('summary_scholarship_applications'); 
        $e = [
            'id' => Text::uuid(),
            'academic_period_id' => $scholarship_current_academic_period_id,
            'scholarship_id' => $scholarship_current_id,
            'scholarship_param_settings' =>  json_encode($param_screenshots),
            'scholarship_gen_stats' =>  json_encode($SCHOLARSHIP_CANDIDATES_STATS),
            'modified' => date('Y-m-d H:i:s'),
            'modified_user_id' => $userId,
            'created' => date('Y-m-d H:i:s'),
            'created_user_id' => $userId
        ];
        $e_data = $SummaryScholarshipApplicationsTable->newEntity($e);
        $SummaryScholarshipApplicationsTable->save($e_data);

        $ScholarshipParamSettings->updateAll(
            [
                'value'=> $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['MARK_CM2_TGP']
            ],
            [
                'code'=>  self::SCHOLARSHIP_PARAM__BOURSE_TGP 
            ]
        );
        $ScholarshipParamSettings->updateAll(
            [
                'value'=> $SCHOLARSHIP_CANDIDATES_STATS['ATTRIBUTION']['MARK_3e_MO']
            ],
            [
                'code'=>  self::SCHOLARSHIP_PARAM__BOURSE_MO
            ]
        );

        /** -----------------------------------------------------------------------
         *              8- NOTIFICATION | RESPONSE 
        * ---------------------------------------------------------------------- */
        $response = array();
        $response [ 'STATUS' ] = 1;
        Log::write('debug', '  GETSCHOLARSHIPELIGIBLESCANDIDATES - End Processing => ' . (json_encode($SCHOLARSHIP_CANDIDATES_STATS)) );
        // $this->response->body( json_encode( $response));
        return new Response(['body' => json_encode( $response), 'type' => 'json' ]);
    }

    /**
     * Check ScholarShip Prams Format 
     * @param $data Array()
     */
    private function checkScholarshipParamsFormat($data){
        $error_o = array();
        
        foreach ($data as $k => $v) {
            $value_trimed =  trim($v['value']);
            if(self::SCHOLARSHIP_PARAMS[ ($v['code'])  ] && !preg_match(self::SCHOLARSHIP_PARAMS[ ($v['code']) ]['FORMAT'],  $value_trimed))
            {
                $error_o = ['CODE'=> $v['code'], "NOM"=> $v['name'], 'VALEUR'=> $value_trimed];
                break;
            }
        }
        return $error_o;
    }
    /**
     * Build Eligible Candidates Entity Data 
     * 
     * @param $p_applicant_id {int} Application ID , 
     * @param p_scholarship_id {int} Scholarship  ID , 
     * @param p_requested_amount {float|int} RequestedAmount 
     * @param p_status_id  {int} StatusID  
     * @param p_assignee_id  {int}  Assignee ID   
     * @param p_comments  {string} Comments    
     * @param p_category {text}  Category  RENOUVELLEMENT 
     * @param p_lot {text} Lot   Ex 1/BOURSIER  
     * @param p_institution_id {int} User Current Institution ID
     * @param p_id {int}  Application ID    
    */
    private function buildEligibleCandidatesApplicationsTableEntity(
        $p_applicant_id, 
        $p_scholarship_id, 
        $p_requested_amount,   
        $p_status_id,   
        $p_assignee_id,   
        $p_category, 
        $p_lot, 
        $p_institution_id, 
        $p_comments = NULL,   
        $p_id=NULL)
    {
        $entityData = [
            'id'=> Text::uuid(),
            'applicant_id' =>  $p_applicant_id,
            'scholarship_id' =>  $p_scholarship_id,
            'requested_amount' =>  $p_requested_amount,
            'status_id' =>  $p_status_id,
            'assignee_id' =>  $p_assignee_id,
            'comments' =>  $p_comments,
            'created_user_id' => 2,
            'created' => date('Y-m-d H:i:s'),
            
            'category' => $p_category,
            'lot' => $p_lot,
            'current_institution_id' => $p_institution_id
        ];

        if($p_id){ $entityData ['id'] = $p_id; }

        return $entityData;
    }
    /** <  ------------ #OET  ScholarshipAutoGetCandidates @Miché ----------------  */


    public function Identities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']);
    }

    public function Nationalities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']);
    }

    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']);
    }

    public function Guardians()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Guardians']);
    }

    public function Histories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.Histories']);
    }

    public function RecipientPaymentStructures()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.RecipientPaymentStructures']);
    }

    public function RecipientPayments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.RecipientPayments']);
    }

    public function RecipientCollections()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Scholarship.RecipientCollections']);
    }

    // end

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $this->Navigation->addCrumb('Scholarships', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Scholarships', 'index']);

        $header = __('Scholarships');
        $alias = $model->alias();
        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $excludedModel = ['Scholarships', 'Applications', 'RecipientPaymentStructures', 'RecipientPayments'];

            if (!in_array($alias, $excludedModel)) {
                $model->toggle('add', false);
                $model->toggle('edit', false);
                $model->toggle('remove', false);

                $applicantId = $this->ControllerAction->getQueryString('applicant_id');
                $header = $this->Users->get($applicantId)->name;

                $this->Navigation->addCrumb('Applications', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Applications', 'index']);
                $this->Navigation->addCrumb($header);
                $this->Navigation->addCrumb($model->getHeader($alias));
            }
        }

        $header .= ' - ' . $model->getHeader($alias);
        $this->set('contentHeader', $header);

        $persona = false;
        $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
        $event = $model->eventManager()->dispatch($event);
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('queryString', $this->request->query)) {
            $applicantId = $this->ControllerAction->getQueryString('applicant_id');

            if ($model->hasField('security_user_id')) {
                $query->where([$model->aliasField('security_user_id') => $applicantId]);
            } else if ($model->hasField('student_id')) {
                $query->where([$model->aliasField('student_id') => $applicantId]);
            } else if ($model->hasField('applicant_id')) {
                $query->where([$model->aliasField('applicant_id') => $applicantId]);
            }
        }
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        /** ------------------- > #OET : Fix Black-Holded Error() --------------- */
        $this->Security->config('unlockedActions', 
        [
            "setScholarshipSchoolValidation",
            "setScholarshipSchoolAllValidation",
            "setScholarshipConfigItem",
        ]);
        /** ------------------- < #OET : Fix Black-Holded Error --------------- */
    }
}
