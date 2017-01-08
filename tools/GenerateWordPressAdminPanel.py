import json


NEWLINE = '\n'


def AddLine(tab, line):
    txt = ''
    tab *= 4
    txt = (tab * ' ') + line
    print(txt)
    return txt + NEWLINE


def LineFeed():
    print
    return NEWLINE


def FormatOptionName(data, section):
    txt = data['Namespace'].upper() + '_' + section['Name'].upper() + '_OPTION'
    return txt


def FormatSettingsSectionIdTag(data, section):
    txt = data['Namespace'].lower() + '_' + section['Name'].lower() + '_settings_section_id'
    return txt


def FormatSettingsFieldIdTag(data, section, field):
    txt = data['Namespace'].lower() + '_' + section['Name'].lower() + '_' + field['Name'].lower() + '_settings_field_id'
    return txt


def FormatSettingsSectionPageTag(data, section):
    txt = data['Namespace'].lower() + '_' + section['Name'].lower() + '_settings_section_page'
    return txt


def FormatSettingsSectionCallBackFunctionName(data, section):
    txt = data['Name'] + 'AdminDrawSettingsHelp' + section['Name']
    return txt


def FormatSettingsFieldCallBackFunctionName(data, section, field):
    txt = data['Name'] + 'AdminDrawSettings' + section['Name'] + field['Name']
    return txt


def CreateRegisterSettingCode(data, tab, section):
    txt = AddLine(tab, 'register_setting(' + FormatOptionName(data, section) + ', ' + FormatOptionName(data, section) + ');');
    return txt


def CreateAddSettingsSectionCode(data, tab, section):
    txt = ''
    txt += AddLine(tab, 'add_settings_section(')
    txt += AddLine(tab + 1, "'" + FormatSettingsSectionIdTag(data, section) + "',")
    txt += AddLine(tab + 1, "'" + section['Title'] + "',")
    txt += AddLine(tab + 1, "'" + data['Namespace'] + '\\' + FormatSettingsSectionCallBackFunctionName(data, section) + "',")
    txt += AddLine(tab + 1, "'" + FormatSettingsSectionPageTag(data, section) + "'")
    txt += AddLine(tab, ');')
    return txt


def CreateAddSettingsFieldCode(data, tab, section, field):
    txt = ''
    txt += AddLine(tab, 'add_settings_field(')
    txt += AddLine(tab + 1, "'" + FormatSettingsFieldIdTag(data, section, field) + "',")
    txt += AddLine(tab + 1, "'" + field['Title'] + "',")
    txt += AddLine(tab + 1, "'" + data['Namespace'] + '\\' + FormatSettingsFieldCallBackFunctionName(data, section, field) + "',")
    txt += AddLine(tab + 1, "'" + FormatSettingsSectionPageTag(data, section) + "',")
    txt += AddLine(tab + 1, "'" + FormatSettingsSectionIdTag(data, section) + "'")
    txt += AddLine(tab, ');')
    return txt


def CreateAddSettingsCode(data, tab, section):
    txt = ''
    txt += CreateAddSettingsSectionCode(data, tab, section)
    for field in section['Fields']:
        txt += CreateAddSettingsFieldCode(data, tab, section, field)
    return txt


def CreatePhpStartCode():
    txt = ''
    txt += AddLine(0, '<?php')
    txt += LineFeed()
    return txt



def CreateNamespaceCode(data):
    txt = ''
    txt += AddLine(0, 'namespace ' + data['Namespace'] + ';')
    txt += LineFeed()
    return txt


def CreateOptionConstants(data):
    txt = ''
    for section in data['Sections']:
        txt += AddLine(0, 'define(\'' + FormatOptionName(data, section) + '\', \'' + FormatOptionName(data, section).lower() + '\');')
    txt += LineFeed()
    return txt


def CreatePluginOptionDefaults(data):
    txt = ''
    txt += AddLine(0, 'function ' + data['Name'] + 'OptionDefault()')
    txt += AddLine(0, '{')

    lines_added = False
    for section in data['Sections']:
        if (section.has_key('ResetDefault') and section['ResetDefault'] == '1') or (data.has_key('ResetDefault') and data['ResetDefault'] == '1'):
            txt += AddLine(1, 'delete_option(' + FormatOptionName(data, section) + ');')
            lines_added = True

    if lines_added:
        txt += LineFeed()

    for section in data['Sections']:
        txt += AddLine(1, 'add_option(')
        txt += AddLine(2, FormatOptionName(data, section) + ', array(')
        n = len(section['Fields'])
        i = 0
        for field in section['Fields']:
            linetxt = '\'' + field['Name'] + '\' => \'' + field['Default'] + '\''
            i = i + 1
            if i<n:
                linetxt = linetxt + ','
            txt += AddLine(3, linetxt)
        txt += AddLine(2, ')')
        txt += AddLine(1, ');')

    txt += AddLine(0, '}')
    txt += LineFeed()
    return txt


def CreateAdminMenuFunctionCode(data):
    txt = ''
    txt += AddLine(0, 'function ' + data['Name'] + 'AdminMenu()')
    txt += AddLine(0, '{')
    for section in data['Sections']:
        txt += CreateRegisterSettingCode(data, 1, section)
    txt += LineFeed()
    for section in data['Sections']:
        txt += CreateAddSettingsCode(data, 1, section)
    txt += AddLine(0, '}')
    txt += LineFeed()
    return txt


def CreateAdminDrawPanelSectionCode(data, tab, section):
    txt = ''
    txt += AddLine(1, 'echo \'<form action="options.php" method="post">\';')
    txt += AddLine(1, 'echo settings_fields(' + FormatOptionName(data, section) + ');')
    txt += AddLine(1, 'echo do_settings_sections(\'' + FormatSettingsSectionPageTag(data, section) + '\');')
    txt += AddLine(1, 'echo \'<input type="submit" name="Submit" value="Save Options" />\';')
    txt += AddLine(1, 'echo \'</form>\';')
    return txt


def CreateAdminDrawPanelPageCode(data):
    txt = ''
    txt += AddLine(0, 'function '+ data['Name'] + 'DrawAdminPage()')
    txt += AddLine(0, '{')
    txt += AddLine(1, 'echo \'<div class="wrap">\';')
    txt += AddLine(1, 'echo \'<h2>' + data['Header'] + '</h2>\';')
    txt += AddLine(1, 'echo \'<p>' + data['Description'] + '</p>\';')
    txt += LineFeed()

    for section in data['Sections']:
        txt += AddLine(1, 'echo \'<hr>\';')
        txt += CreateAdminDrawPanelSectionCode(data, 1, section)
        txt += LineFeed()

    txt += AddLine(1, 'echo \'</div>\';')
    txt += AddLine(0, '}')
    txt += LineFeed()
    return txt


def CreateAdminPageSectionHelpCode(data, section):
    txt = ''
    txt += AddLine(0, 'function '+ FormatSettingsSectionCallBackFunctionName(data, section) + '()')
    txt += AddLine(0, '{')
    txt += AddLine(1, 'echo \'<p>' + section['Description'] + '</p>\';')
    txt += AddLine(0, '}')
    txt += LineFeed()
    return txt


def CreateSectionHelpFunctions(data):
    txt = ''
    for section in data['Sections']:
        txt += CreateAdminPageSectionHelpCode(data, section)
    return txt


def CreateSectionFieldFunction(data):
    txt = ''
    for section in data['Sections']:
        for field in section['Fields']:
            txt += AddLine(0, 'function ' + FormatSettingsFieldCallBackFunctionName(data, section, field) + '()')
            txt += AddLine(0, '{')
            txt += AddLine(1, '$options = get_option(' + FormatOptionName(data, section) + ');')
            txt += AddLine(1, '$selected = $options[\'' + field['Name'] + '\'];')

            if(field['Type'] == 'checkbox'):
                txt += AddLine(1, 'echo \'<input name="\' . ' + FormatOptionName(data, section) + ' . \'[' + field['Name'] + ']" type="checkbox" value="1" \' . checked(1, $selected, false) . \' />\';')
            elif (field['Type'] == 'text'):
                txt += AddLine(1, 'echo \'<input name="\' . ' + FormatOptionName(data, section) + ' . \'[' + field['Name'] + ']" type="text" value="\' . $selected . \'" />\';')
            elif (field['Type'] == 'textarea'):
                txt += AddLine(1, 'echo \'<textarea rows="' + field['Rows'] + '" cols="' + field['Columns'] + '" name="\' . ' + FormatOptionName(data, section) + ' . \'[' + field['Name'] + ']" type="text">\'.$selected.\'</textarea>\';')
            else:
                print
                print('ERROR missing generator for input type "' + field['Type'] + '"')
                raise

            txt += AddLine(0, '}')
            txt += LineFeed()
    return txt


def GenerateAdminPanel(filename):
    source_file = filename + '.json'
    target_file = filename + '.php'

    read_file = open(source_file, 'r')
    data = json.load(read_file)

    output_txt = CreatePhpStartCode()
    output_txt = output_txt + CreateNamespaceCode(data)
    output_txt = output_txt + CreateOptionConstants(data)
    output_txt = output_txt + CreatePluginOptionDefaults(data)
    output_txt = output_txt + CreateAdminMenuFunctionCode(data)
    output_txt = output_txt + CreateAdminDrawPanelPageCode(data)
    output_txt = output_txt + CreateSectionHelpFunctions(data)
    output_txt = output_txt + CreateSectionFieldFunction(data)

    print('Write to:' + target_file)
    f2 = open(target_file,'w')
    f2.write(output_txt)
    f2.close()
    print('Done')


GenerateAdminPanel('../inc/membership_admin')
