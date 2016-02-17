Things to migrate
Settings
- logtolocation > logpath
- bannerxmllocation > xmlpath
- bannerxmlfolder > extractpath






# Docs
* Assumes that if there is a `<?xml` or `<!DOCTYPE` in the start of the doc, it is well formed
* Otherwise, it wraps the input in <lmb> tags to make sure it works correctly


# Features to add
* Progress object for parsing
* Use MD5 to see if we have run a file, not timestamp
* Option to reprocess from DB when settings change

# TODO
* Doc mappings.json

