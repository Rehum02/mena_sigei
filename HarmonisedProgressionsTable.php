<?php
namespace HarmonisedProgression\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

use Cake\ORM\RulesChecker;
use Cake\Log\Log;

/**
 * HarmonisedProgressions Model
 *
 * @property \Cake\ORM\Association\BelongsTo $EducationGrades
 * @property \Cake\ORM\Association\BelongsTo $EducationSubjects
 * @property \Cake\ORM\Association\BelongsTo $ModifiedUsers
 * @property \Cake\ORM\Association\BelongsTo $CreatedUsers
 * @property \Cake\ORM\Association\HasMany $EducationContentCompetences
 *
 * @method \HarmonisedProgression\Model\Entity\HarmonisedProgression get($primaryKey, $options = [])
 * @method \HarmonisedProgression\Model\Entity\HarmonisedProgression newEntity($data = null, array $options = [])
 * @method \HarmonisedProgression\Model\Entity\HarmonisedProgression[] newEntities(array $data, array $options = [])
 * @method \HarmonisedProgression\Model\Entity\HarmonisedProgression|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \HarmonisedProgression\Model\Entity\HarmonisedProgression patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \HarmonisedProgression\Model\Entity\HarmonisedProgression[] patchEntities($entities, array $data, array $options = [])
 * @method \HarmonisedProgression\Model\Entity\HarmonisedProgression findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class HarmonisedProgressionsTable extends ControllerActionTable
{

    use OptionsTrait;
    private $autoAllocationOptions = [];

    // protected $hidden = array('modified_user_id', 'modified', 'created_user_id', 'created');

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->setDeleteStrategy('restrict');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        return $validator;
    }
    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['education_grade_id'], 'EducationGrades'));
        $rules->add($rules->existsIn(['education_subject_id'], 'EducationSubjects'));
        $rules->add($rules->existsIn(['academic_period_id'], 'AcademicPeriods'));
        return $rules;
    }



    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    /** -------------------- ACTIONS EVENT : i.e  --------------------------- */
    public function afterAction(Event $event, ArrayObject $extra)
    {
    }

    /** -------------------- * QUERY   -------------------- */
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    } 
    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
    }

    /** -------------------- POST QUERY  : i.e WEBHOOKS ----------------- */

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // Eventual Webhook Here ... Create/Update
    }
    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        // Eventual Webhook Here ...
    }

    /** -------------------- onGet* : EVENTS   ----------------- */

    /** -------------------- onUpdate* : EVENTS   ----------------- */
 
    /** -------------------- onDelete* : EVENTS   ----------------- */
    
    /** -------------------- * GLOBAL  -------------------- */

}
