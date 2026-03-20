<?php

declare (strict_types=1);
namespace Json_Schema\Constraints\Drafts\Draft07;

class Factory extends \Json_Schema\Constraints\Factory
{
    /**
     * @var array<string, class-string>
     */
    protected $constraint_map = ['schema' => Draft07Constraint::class, 'additionalProperties' => Additional_Properties_Constraint::class, 'additionalItems' => Additional_Items_Constraint::class, 'dependencies' => Dependencies_Constraint::class, 'type' => Type_Constraint::class, 'const' => Const_Constraint::class, 'enum' => Enum_Constraint::class, 'uniqueItems' => Unique_Items_Constraint::class, 'minItems' => Min_Items_Constraint::class, 'minProperties' => Min_Properties_Constraint::class, 'maxProperties' => Max_Properties_Constraint::class, 'minimum' => Minimum_Constraint::class, 'maximum' => Maximum_Constraint::class, 'exclusiveMinimum' => Exclusive_Minimum_Constraint::class, 'minLength' => Min_Length_Constraint::class, 'maxLength' => Max_Length_Constraint::class, 'maxItems' => Max_Items_Constraint::class, 'exclusiveMaximum' => Exclusive_Maximum_Constraint::class, 'multipleOf' => Multiple_Of_Constraint::class, 'required' => Required_Constraint::class, 'format' => Format_Constraint::class, 'anyOf' => Any_Of_Constraint::class, 'allOf' => All_Of_Constraint::class, 'oneOf' => One_Of_Constraint::class, 'not' => Not_Constraint::class, 'ifThenElse' => If_Then_Else_Constraint::class, 'contains' => Contains_Constraint::class, 'propertyNames' => Properties_Names_Constraint::class, 'patternProperties' => Pattern_Properties_Constraint::class, 'pattern' => Pattern_Constraint::class, 'properties' => Properties_Constraint::class, 'items' => Items_Constraint::class, 'ref' => Ref_Constraint::class, 'content' => Content_Constraint::class];
}