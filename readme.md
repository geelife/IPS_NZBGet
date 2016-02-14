### Available API calls from NZBGet
### https://github.com/nzbget/nzbget/wiki/API
## Program control
#version                             - implemented (Module)
#shutdown                            - implemented (Module)
#reload                              - implemented (Module) (e.g. after changing settings)

## Queue and history
#listgroups                          - implemented (Module)
#listfiles                           - implemented (Module - no import)
#history                             - implemented (Module - no import)
#append                              - implemented (Module - testing required)
#editqueue                           - not implemented
#scan                                - implemented (Module)

## Status, logging and statistics
#status                             - implemented  (Module)
#log                                - implemented  (Module-HTML)
#writelog                           - implemented  (Module) (e.g. after IPS server executed post scripts such as file move or if added NZBs to scan folder)
#loadlog                            - implemented  (not tested)
#servervolumes                      - not implemented (official documentation missing)
#resetservervolume                  - implemented (Module - testing required)

## Pause and speed limit
#rate                               - implemented (Module) (set speed limit in kb/s (int): e.g. if you leave your home set it to 0(unlimited) and limit it if you are at home)
#pausedownload                      - implemented (Module)
#resumedownload                     - implemented (Module)
#pausepost                          - implemented (Module)
#resumepost                         - implemented (Module)
#pausescan                          - implemented (Module) (pause scan of NzbDir)
#resumescan                         - implemented (Module) (resume scan of NzbDir)
#scheduleresume                     - implemented (Module) (schedule to resume all activities after specified seconds (int)

## Configuration
#config                             - implemented (Module - no import)
#loadconfig                         - implemented (Module)
#saveconfig                         - implemented (Module)
#configtemplates                    - implemented (Module)