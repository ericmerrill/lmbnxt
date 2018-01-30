Things to migrate
Settings
- logtolocation > logpath
- bannerxmllocation > xmlpath
- bannerxmlfolder > extractpath
- ignoreemailcase > lowercaseemails
- ignoreusernamecase > (delete)
- usernamesource (moved to constants)
- cattype (moved to constants) (Note that selected is 'other' in old settings)




# Docs
* Assumes that if there is a `<?xml` or `<!DOCTYPE` in the start of the doc, it is well formed
* Otherwise, it wraps the input in <lmb> tags to make sure it works correctly
* ignoreusernamecaseis now assumed

# Object types


# Features to add
* Progress object for parsing
* Use MD5 to see if we have run a file, not timestamp
* Option to reprocess from DB when settings change
* Settings controller/change settings based on input
* Use internal ID instead of SDID or G# in ID Number

# LIS 2 improvements
* LIS group term allows restrict flag, even though ILP doesn't seem to use
* Deal with possible namespaces in XML
* A person MembershipRequest technically allows multiple role fields
* Improve role type conversions and make into settings.
* Possible option to 'match' sources between LMB and LIS.

# Moodle
* Deal with users that have more than one role assignment in a course.
* Handle term name changes

# Misc
* Doc mappings.json
* General dev docs
* Deal with SourceDID vs SpridenID (sdid vs G#)
* Drop sourcedidsource requirements.
* Add caching in key places? Like term lookups

