<?php

namespace likingfit\Workflow\Parser;

class EntityNames {
    const TAG_DESCRIPTION = 'fpdl:Description';
    const TAG_STARTNODE = 'fpdl:StartNode';
    const TAG_ACTIVITES = 'fpdl:Activities';
    const TAG_ACTIVITY = 'fpdl:Activity';
    const TAG_TASKS = 'fpdl:Tasks';
    const TAG_TASK = 'fpdl:Task';
    const TAG_EDITFORM = 'fpdl:EditForm';
    const TAG_PERFORMER = 'fpdl:Performer';
    const TAG_VIEWFORM = 'fpdl:ViewForm';
    const TAG_LISTFORM = 'fpdl:ListForm';
    const TAG_APPLICATION = 'fpdl:Application';
    const TAG_URI = 'fpdl:Uri';
    const TAG_ASSIGNMENTHANDLER = 'fpdl:AssignmentHandler'; 
    const TAG_TOOLTASK_HANDLER = 'fpdl:Handler';
    const TAG_SYNCHRONIZERS = 'fpdl:Synchronizers';
    const TAG_SYNCHRONIZER = 'fpdl:Synchronizer';
    const TAG_TRANSITIONS = 'fpdl:Transitions';
    const TAG_TRANSITION = 'fpdl:Transition';
    const TAG_ENDNODES = 'fpdl:EndNodes';
    const TAG_ENDNODE = 'fpdl:EndNode';
    const TAG_CONDITION = 'fpdl:Condition';
    const TAG_DATAFIELDS = 'fpdl:DataFields';
    const TAG_DATAFIELD = 'fpdl:DataField';
    const TAG_LOOPS = 'fpdl:Loops';
    const TAG_LOOP = 'fpdl:Loop';
    const TAG_SUBFLOWWORKFLOWPROCESS = 'fpdl:SubWorkflowProcess';
    const TAG_SUBFLOWWORKFLOWPROCESSID = 'fpdl:WorkflowProcessId';
    
    const ATTRIBUTE_TASK_TYPE = 'Type';
    const ATTRIBUTE_TASK_TOOL = 'TOOL';
    const ATTRIBUTE_TASK_FORM = 'FORM';
    
    const NODE_FROM = 'from';
    const NODE_TO = 'to';
    
    const DATAFIELD_VALUE = 'InitialValue';
    const ATTRIBUTE_NAME = 'Name';
    
    const TASK_INSTANCE_CREATOR = "TaskInstanceCreator";
    const TASK_INSTANCE_RUNNER = "TaskInstanceRunner";
    const TASK_INSTANCE_COMPLETION_EVALUATOR = "TaskInstanceCompletionEvaluator";
    
    const FORM_TASK_INSTANCE_RUNNER = "FormTaskInstanceRunner";
    const TOOL_TASK_INSTANCE_RUNNER = "ToolTaskInstanceRunner";
    const SUBFLOW_TASK_INSTANCE_RUNNER = "SubflowTaskInstanceRunner";
    
    const FORM_TASK_INSTANCE_COMPLETION_EVALUATOR = "FormTaskInstanceCompletionEvaluator";
    const TOOL_TASK_INSTANCE_COMPLETION_EVALUATOR = "ToolTaskInstanceCompletionEvaluator";
    const SUBFLOW_TASK_INSTANCE_COMPLETION_EVALUATOR = "SubflowTaskInstanceCompletionEvaluator";
    
    const ACTIVITY_DEPENDENCY = 'Dependency';
}