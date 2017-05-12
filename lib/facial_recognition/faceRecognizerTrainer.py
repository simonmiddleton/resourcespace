import os, sys, csv, cv2
import numpy as np

if 1 == len(sys.argv):
    print >> sys.stderr, 'Try running: FaceRecognizerTrainer.py <path/to/prepared_data.csv>'
    sys.exit(1)

# Init
PREPARED_DATA_PATH = sys.argv[1]
images             = []
labels             = []

if not os.path.isfile(PREPARED_DATA_PATH):
    print >> sys.stderr, "No file at '{}'".format(PREPARED_DATA_PATH)
    sys.exit(1)

with open(PREPARED_DATA_PATH, 'rb') as csvFile:
    for csvRow in csv.reader(csvFile, delimiter = ';', quotechar = '"'):
        # Expected row should contain: ['/path/to/file', 'label']
        if '' != csvRow[0] and '' != csvRow[1]:
            images.append(cv2.imread(csvRow[0], 0))
            labels.append(int(csvRow[1]))

if(0 == len(images)):
    print >> sys.stderr, 'No data found for training!'
    sys.exit(1)

# Create an LBPH model for face recognition and train it
# with the images and labels read from the given CSV file
model = cv2.createLBPHFaceRecognizer()
model.train(images, np.array(labels))
model.save("{}/lbph_model.xml".format(os.path.dirname(PREPARED_DATA_PATH)))

print 'Successfully trained FaceRecognizer!'