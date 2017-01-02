<?php
namespace Workflow\Form;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilter;
use Zend\Validator;
use Zend\Filter;

class ConfirmForm extends Form
{

    public function __construct()
    {
        parent::__construct('confirm');

        $this->setAttribute('method', 'post');
        $this->setAttribute('accept-charset', 'UTF-8');

        $this->addElements();
        $this->addInputFilter();
    }

    /**
     * @return void
     */
    protected function addElements()
    {
        $element = new Element\Csrf('token');
        $element->setCsrfValidatorOptions([
            'timeout' => 900,
        ]);
        $this->add($element);

        $element = new Element\Hidden('moduleName');
        $this->add($element);

        $element = new Element\Hidden('id');
        $this->add($element);

        $element = new Element\Submit('confirm');
        $element->setValue('Confirm');
        $this->add($element);
    }

    /**
     * @return ConfirmForm
     */
    protected function addInputFilter()
    {
        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'moduleName',
            'allow_empty' => true,
            'validators' => [
                [
                    'name' => Validator\InArray::class,
                    'options' => [
                        'haystack' => ['block', 'item'],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name' => 'id',
            'filters' => [
                ['name' => Filter\ToInt::class],
            ],
        ]);

        return $this->setInputFilter($inputFilter);
    }

}
